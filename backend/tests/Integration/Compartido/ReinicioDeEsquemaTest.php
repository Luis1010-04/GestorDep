<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Identificador;

/*
 * El reinicio rapido entre pruebas (tests/Ayudantes/EsquemaDePrueba.php) debe dejar la base como `migrate:fresh`.
 * Estas pruebas comparan ambos estados y comprueban que el DDL sucio fuerza la reconstruccion.
 */

/** @return array<string, mixed> */
function reinicioInstantanea(): array
{
    $conexion = DB::connection('propietario');
    $filas = [];
    foreach ($conexion->select("select tablename from pg_tables where schemaname = 'public' order by tablename") as $tabla) {
        $filas[$tabla->tablename] = $conexion->table($tabla->tablename)->count();
    }

    return [
        'filas' => $filas,
        'indices' => $conexion->select("select indexname, indexdef from pg_indexes where schemaname = 'public' order by indexname"),
        'disparadores' => $conexion->select(
            "select c.relname, t.tgname, pg_get_triggerdef(t.oid) as definicion from pg_trigger t join pg_class c on c.oid = t.tgrelid
              where not t.tgisinternal and c.relnamespace = 'public'::regnamespace order by c.relname, t.tgname",
        ),
        'funciones' => $conexion->select("select proname, md5(prosrc) as huella from pg_proc where pronamespace = 'public'::regnamespace order by proname, huella"),
        'restricciones' => $conexion->select(
            "select conrelid::regclass::text as tabla, conname, pg_get_constraintdef(oid) as definicion from pg_constraint
              where connamespace = 'public'::regnamespace order by tabla, conname",
        ),
        'permisos' => $conexion->select("select relname, relacl::text as acl from pg_class where relnamespace = 'public'::regnamespace and relkind in ('r', 'S') order by relname"),
        'secuencias' => $conexion->select("select sequencename, last_value from pg_sequences where schemaname = 'public' order by sequencename"),
        'estructura' => esquemaEstructura(),
    ];
}

function reinicioDisparadorDePrueba(): int
{
    return (int) DB::connection('propietario')->scalar("select count(*) from pg_trigger where tgname = 'tg_viva_de_prueba'");
}

/** Deja datos en todas las capas: negocio, historial, sesion, cola y una secuencia que avanza sin filas. */
function reinicioEnsuciar(): string
{
    $mundo = mundoDeJugadores();
    $conexion = DB::connection('propietario');
    $conexion->table('jugadores_historial')->insert([
        'id' => Identificador::generar()->valor(),
        'jugador_id' => $mundo['j1'],
        'version' => 1,
        'operacion' => 'crear',
        'datos_nuevos' => json_encode(['dato' => 'x']),
        'usuario_id' => null,
        'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);
    $conexion->table('sessions')->insert(['id' => 'sesion-de-prueba', 'payload' => 'x', 'last_activity' => 1]);
    $conexion->table('trabajos')->insert(['queue' => 'x', 'payload' => '{}', 'attempts' => 0, 'available_at' => 0, 'created_at' => 0]);
    $secuencia = (string) $conexion->scalar("select sequencename from pg_sequences where schemaname = 'public' and sequencename <> 'migrations_id_seq' order by sequencename limit 1");
    $conexion->select('select nextval(?)', [$secuencia]);

    return $secuencia;
}

test('F1-H1-FIX: el reinicio rapido deja la misma base que migrate:fresh (filas, indices, disparadores, funciones, restricciones, permisos y secuencias)', function () {
    esquemaReconstruir();
    $fresco = reinicioInstantanea();

    $secuencia = reinicioEnsuciar();
    expect($secuencia)->not->toBe('')
        ->and(reinicioInstantanea())->not->toEqual($fresco);

    expect(esquemaReiniciar())->toBe('rapido')
        ->and(reinicioInstantanea())->toEqual($fresco);
});

test('F1-H1-FIX: tras el reinicio rapido el historial sigue siendo de solo insercion', function () {
    esquemaReconstruir();
    reinicioEnsuciar();
    esquemaReiniciar();
    $mundo = mundoDeJugadores();
    $propietario = DB::connection('propietario');
    $propietario->table('jugadores_historial')->insert([
        'id' => Identificador::generar()->valor(),
        'jugador_id' => $mundo['j1'],
        'version' => 1,
        'operacion' => 'crear',
        'datos_nuevos' => json_encode(['dato' => 'x']),
        'usuario_id' => null,
        'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);

    foreach ([
        'propietario' => fn () => $propietario->table('jugadores_historial')->update(['motivo' => 'x']),
        'propietario borrando' => fn () => $propietario->table('jugadores_historial')->delete(),
        'aplicacion' => fn () => DB::table('jugadores_historial')->update(['motivo' => 'x']),
        'aplicacion borrando' => fn () => DB::table('jugadores_historial')->delete(),
    ] as $quien => $operacion) {
        $rechazada = false;
        try {
            $operacion();
        } catch (QueryException) {
            $rechazada = true;
        }
        expect($rechazada)->toBeTrue($quien);
    }
    expect($propietario->table('jugadores_historial')->count())->toBe(1);
});

test('F1-H1-FIX: esquemaMarcarSucio fuerza una reconstruccion completa y la siguiente vuelve a ser rapida', function () {
    esquemaReconstruir();
    expect(esquemaReiniciar())->toBe('rapido');

    esquemaMarcarSucio();

    expect(esquemaReiniciar())->toBe('completo')
        ->and(esquemaReiniciar())->toBe('rapido');
});

test('F1-H1-FIX: un disparador que quedo vivo sin marcar el esquema tambien fuerza la reconstruccion', function () {
    esquemaMarcarSucio();
    esquemaReconstruir();
    $propietario = DB::connection('propietario');

    try {
        $propietario->unprepared("create or replace function falla_viva_de_prueba() returns trigger language plpgsql as \$\$ begin raise exception 'viva'; end \$\$");
        $propietario->unprepared('create trigger tg_viva_de_prueba before insert on categorias for each row execute function falla_viva_de_prueba()');
        expect(reinicioDisparadorDePrueba())->toBe(1);

        expect(esquemaReiniciar())->toBe('completo')
            ->and(reinicioDisparadorDePrueba())->toBe(0);
    } finally {
        $propietario->unprepared('drop trigger if exists tg_viva_de_prueba on categorias');
        $propietario->unprepared('drop function if exists falla_viva_de_prueba()');
    }
});
