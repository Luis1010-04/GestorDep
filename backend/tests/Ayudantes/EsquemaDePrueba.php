<?php

/*
 * Reinicio del esquema entre pruebas. `migrate:fresh` cuesta ~1,1 s y lo pagaban ~300 pruebas; aqui el esquema se
 * migra una vez por ejecucion y entre pruebas solo se vacian las tablas que tienen filas (las migraciones no
 * siembran nada: tras `migrate:fresh` todas las tablas estan vacias, y cada prueba siembra lo que necesita).
 *
 * Una prueba que ejecuta DDL (disparadores, ALTER, DROP, rollback de migraciones) llama antes a
 * `esquemaMarcarSucio()` y la siguiente prueba reconstruye el esquema con `migrate:fresh`. Como red de seguridad,
 * si la estructura de la base (tablas, indices, disparadores, funciones, restricciones y permisos) ya no coincide
 * con la que dejo la ultima migracion completa, tambien se reconstruye.
 */

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;

use function Pest\Laravel\artisan;

/**
 * @param  array{preparado: bool, sucio: bool, estructura: string}|null  $nuevo
 * @return array{preparado: bool, sucio: bool, estructura: string}
 */
function esquemaEstadoDePrueba(?array $nuevo = null): array
{
    static $estado = ['preparado' => false, 'sucio' => false, 'estructura' => ''];
    if ($nuevo !== null) {
        $estado = $nuevo;
    }

    return $estado;
}

/** Obliga a que la siguiente llamada a `esquemaReiniciar()` reconstruya el esquema con `migrate:fresh`. */
function esquemaMarcarSucio(): void
{
    esquemaEstadoDePrueba([...esquemaEstadoDePrueba(), 'sucio' => true]);
}

/** Huella de la estructura de la base: si cambia, el esquema ya no es el que dejan las migraciones. */
function esquemaEstructura(): string
{
    return (string) DB::connection('propietario')->scalar(<<<'SQL'
        select md5(concat_ws('|',
            (select coalesce(string_agg(tablename, ',' order by tablename), '')
               from pg_tables where schemaname = 'public'),
            (select coalesce(string_agg(indexdef, ',' order by indexname), '')
               from pg_indexes where schemaname = 'public'),
            (select coalesce(string_agg(c.relname || ':' || t.tgname || ':' || pg_get_triggerdef(t.oid), ',' order by c.relname, t.tgname), '')
               from pg_trigger t join pg_class c on c.oid = t.tgrelid
              where not t.tgisinternal and c.relnamespace = 'public'::regnamespace),
            (select coalesce(string_agg(proname || ':' || md5(prosrc), ',' order by proname, prosrc), '')
               from pg_proc where pronamespace = 'public'::regnamespace),
            (select coalesce(string_agg(conrelid::regclass::text || ':' || conname || ':' || pg_get_constraintdef(oid), ',' order by conrelid::regclass::text, conname), '')
               from pg_constraint where connamespace = 'public'::regnamespace),
            (select coalesce(string_agg(relname || ':' || coalesce(relacl::text, ''), ',' order by relname), '')
               from pg_class where relnamespace = 'public'::regnamespace and relkind in ('r', 'S'))
        ))
        SQL);
}

/**
 * Borra las filas de las tablas dadas. `TRUNCATE` cuesta ~0,5 s por tabla en el volumen de Docker (crea archivos y los
 * sincroniza), así que se usa `DELETE` en orden de claves foráneas (hijas antes que padres), con los disparadores de
 * solo inserción de esas tablas desactivados dentro de la misma transacción. Si las claves foráneas forman un ciclo,
 * se vuelve a `TRUNCATE`.
 *
 * @param  list<string>  $tablas
 */
function esquemaBorrarFilas(ConnectionInterface $conexion, array $tablas): void
{
    $orden = esquemaOrdenDeBorrado($tablas, $conexion->select(
        "select conrelid::regclass::text as hija, confrelid::regclass::text as padre
           from pg_constraint where contype = 'f' and connamespace = 'public'::regnamespace",
    ));

    if ($orden === null) {
        $conexion->statement('truncate table "'.implode('", "', $tablas).'" restart identity cascade');

        return;
    }

    $conexion->transaction(function () use ($conexion, $orden): void {
        $conDisparadores = array_map(
            fn (stdClass $fila): string => $fila->tabla,
            $conexion->select(
                "select distinct c.relname as tabla from pg_trigger t join pg_class c on c.oid = t.tgrelid
                  where not t.tgisinternal and c.relnamespace = 'public'::regnamespace and c.relname in ('".implode("', '", $orden)."')",
            ),
        );

        foreach ($conDisparadores as $tabla) {
            $conexion->statement("alter table \"{$tabla}\" disable trigger user");
        }
        foreach ($orden as $tabla) {
            $conexion->statement("delete from \"{$tabla}\"");
        }
        foreach ($conDisparadores as $tabla) {
            $conexion->statement("alter table \"{$tabla}\" enable trigger user");
        }
    });
}

/**
 * @param  list<string>  $tablas
 * @param  array<int, stdClass>  $dependencias  claves foráneas: la tabla `hija` referencia a `padre`
 * @return list<string>|null las tablas con las hijas antes que sus padres; nulo si hay un ciclo
 */
function esquemaOrdenDeBorrado(array $tablas, array $dependencias): ?array
{
    $pendientes = array_fill_keys($tablas, true);
    $orden = [];

    while ($pendientes !== []) {
        $libres = array_filter(
            array_keys($pendientes),
            function (string $tabla) use ($pendientes, $dependencias): bool {
                foreach ($dependencias as $dependencia) {
                    if ($dependencia->padre === $tabla && $dependencia->hija !== $tabla && isset($pendientes[$dependencia->hija])) {
                        return false;
                    }
                }

                return true;
            },
        );

        if ($libres === []) {
            return null;
        }

        foreach ($libres as $tabla) {
            $orden[] = $tabla;
            unset($pendientes[$tabla]);
        }
    }

    return $orden;
}

/** Vacia las tablas con filas y reinicia las secuencias que avanzaron; deja la base como la deja `migrate:fresh`. */
function esquemaVaciarDatos(): void
{
    $conexion = DB::connection('propietario');

    $tablas = array_map(
        fn (stdClass $fila): string => $fila->tablename,
        $conexion->select("select tablename from pg_tables where schemaname = 'public' and tablename <> 'migrations' order by tablename"),
    );
    $consultas = array_map(fn (string $tabla): string => "select '{$tabla}' as tabla where exists (select 1 from \"{$tabla}\")", $tablas);
    $conDatos = array_map(fn (stdClass $fila): string => '"'.$fila->tabla.'"', $conexion->select(implode(' union all ', $consultas)));

    if ($conDatos !== []) {
        esquemaBorrarFilas($conexion, array_values(array_map(fn (string $tabla): string => trim($tabla, '"'), $conDatos)));
    }

    $secuencias = $conexion->select(
        "select sequencename from pg_sequences where schemaname = 'public' and last_value is not null and sequencename <> 'migrations_id_seq'",
    );
    foreach ($secuencias as $secuencia) {
        $conexion->statement('alter sequence "'.$secuencia->sequencename.'" restart');
    }
}

/** Reconstruye el esquema con `migrate:fresh`, tal como lo hace `make fresh` sin sembrar. */
function esquemaReconstruir(): void
{
    esquemaMarcarSucio();
    consolaArtisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    esquemaEstadoDePrueba(['preparado' => true, 'sucio' => false, 'estructura' => esquemaEstructura()]);
}

/**
 * Deja la base vacia y con el esquema de las migraciones, igual que `migrate:fresh`.
 *
 * @return 'rapido'|'completo' como se logro: vaciando datos o reconstruyendo el esquema
 */
function esquemaReiniciar(): string
{
    $estado = esquemaEstadoDePrueba();

    if ($estado['preparado'] && ! $estado['sucio'] && esquemaEstructura() === $estado['estructura']) {
        esquemaVaciarDatos();

        return 'rapido';
    }

    esquemaReconstruir();

    return 'completo';
}

/**
 * `artisan()` de Pest devuelve `PendingCommand|int`; las pruebas encadenan expectativas, así que aquí se acota el tipo.
 *
 * @param  array<string, mixed>  $parametros
 */
function consolaArtisan(string $comando, array $parametros = []): PendingCommand
{
    $pendiente = artisan($comando, $parametros);
    assert($pendiente instanceof PendingCommand);

    return $pendiente;
}
