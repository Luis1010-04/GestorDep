<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;

const TABLAS_DE_PLANTEL = ['categorias', 'posiciones', 'jugadores', 'equipos', 'jugador_equipo', 'entrenador_equipo', 'bajas_jugador'];

const HISTORIALES_DE_PLANTEL = [
    'jugadores_historial' => 'jugador_id',
    'equipos_historial' => 'equipo_id',
    'jugador_equipo_historial' => 'jugador_equipo_id',
    'entrenador_equipo_historial' => 'entrenador_equipo_id',
    'bajas_jugador_historial' => 'baja_jugador_id',
];

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function plantelInsertar(string $tabla, array $atributos): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table($tabla)->insert(['id' => $id, ...$atributos]);

    return $id;
}

function plantelCategoria(string $nombre = 'Sub-17'): string
{
    return plantelInsertar('categorias', ['nombre' => $nombre]);
}

/** @param  array<string, mixed>  $atributos */
function plantelJugador(array $atributos = []): string
{
    return plantelInsertar('jugadores', [
        'nombres' => 'Jugador',
        'apellidos' => 'De Prueba',
        'fecha_nacimiento' => '2009-05-10',
        'matricula' => 'M-001',
        ...$atributos,
    ]);
}

/** @param  array<string, mixed>  $atributos */
function plantelEquipo(array $atributos = []): string
{
    $categoria = $atributos['categoria_id'] ?? plantelCategoria('Categoria '.Identificador::generar()->valor());

    return plantelInsertar('equipos', ['nombre' => 'Aguilas', ...$atributos, 'categoria_id' => $categoria]);
}

function plantelPosicion(string $codigo = 'portero'): string
{
    return plantelInsertar('posiciones', ['codigo' => $codigo, 'nombre' => ucfirst($codigo)]);
}

function plantelUsuario(): string
{
    return plantelInsertar('usuarios', [
        'nombre' => 'Entrenador de prueba',
        'correo' => Identificador::generar()->valor().'@example.test',
        'contrasena_hash' => 'hash-de-prueba',
    ]);
}

function plantelRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

test('RNF-03: dos jugadores con la misma matricula se rechazan', function () {
    plantelJugador(['matricula' => 'M-100']);

    expect(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-100'])))->toBe('23505');
});

test('RNF-03: la matricula se puede reutilizar si el primer registro tiene borrado logico', function () {
    $primero = plantelJugador(['matricula' => 'M-200']);
    DB::connection('propietario')->table('jugadores')->where('id', $primero)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-200'])))->toBeNull();
});

test('RNF-03: la base rechaza una matricula que solo difiere en mayusculas', function () {
    plantelJugador(['matricula' => 'm-1']);

    expect(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-1'])))->toBe('23505');
});

test('RNF-03: la matricula en otras mayusculas se permite si el primer registro tiene borrado logico', function () {
    $primero = plantelJugador(['matricula' => 'm-2']);
    DB::connection('propietario')->table('jugadores')->where('id', $primero)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-2'])))->toBeNull();
});

test('RNF-03: la columna matricula sigue siendo varchar(30) y el indice es parcial sobre lower(matricula)', function () {
    $columna = DB::connection('propietario')->selectOne(
        "select data_type, character_maximum_length from information_schema.columns where table_name = 'jugadores' and column_name = 'matricula'",
    );
    $indice = DB::connection('propietario')->scalar("select indexdef from pg_indexes where indexname = 'uq_jugadores_matricula'");

    expect($columna->data_type)->toBe('character varying')
        ->and($columna->character_maximum_length)->toBe(30)
        ->and($indice)->toContain('lower((matricula)::text)')->toContain('WHERE (eliminado_en IS NULL)');
});

test('RNF-03: revertir la migracion del indice restaura el indice anterior', function () {
    esquemaMarcarSucio();
    $indiceNuevo = DB::connection('propietario')->scalar("select indexdef from pg_indexes where indexname = 'uq_jugadores_matricula'");
    expect($indiceNuevo)->toContain('lower(');

    consolaArtisan('migrate:rollback', [
        '--database' => 'propietario',
        '--force' => true,
        '--path' => 'database/migrations/plantel/2026_10_21_020800_indice_de_matricula_sin_mayusculas.php',
    ])->assertSuccessful();

    $indice = DB::connection('propietario')->scalar("select indexdef from pg_indexes where indexname = 'uq_jugadores_matricula'");
    expect($indice)->toBe('CREATE UNIQUE INDEX uq_jugadores_matricula ON public.jugadores USING btree (matricula) WHERE (eliminado_en IS NULL)');
});

test('RNF-03: jugadores.estatus solo admite activo o baja y por defecto es activo', function () {
    $id = plantelJugador();
    $fila = DB::connection('propietario')->table('jugadores')->where('id', $id)->first();

    expect($fila->estatus_academico)->toBe('activo')
        ->and($fila->estatus_deportivo)->toBe('activo')
        ->and(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-300', 'estatus_academico' => 'suspendido'])))->toBe('23514')
        ->and(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-301', 'estatus_deportivo' => 'lesionado'])))->toBe('23514')
        ->and(plantelRechazo(fn () => plantelJugador(['matricula' => 'M-302', 'estatus_academico' => 'baja', 'estatus_deportivo' => 'baja'])))->toBeNull();
});

test('RNF-03: dos categorias con el mismo nombre en otras mayusculas chocan', function () {
    plantelCategoria('Sub-17');

    expect(plantelRechazo(fn () => plantelCategoria('sub-17')))->toBe('23505');
});

test('RNF-03: el indice de categoria permite reutilizar el nombre tras el borrado logico', function () {
    $primera = plantelCategoria('Sub-15');
    DB::connection('propietario')->table('categorias')->where('id', $primera)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(plantelRechazo(fn () => plantelCategoria('Sub-15')))->toBeNull();
});

test('RNF-03: el indice de equipo permite reutilizar el nombre tras el borrado logico', function () {
    $categoria = plantelCategoria();
    $primero = plantelEquipo(['nombre' => 'Lobos', 'categoria_id' => $categoria]);

    expect(plantelRechazo(fn () => plantelEquipo(['nombre' => 'Lobos', 'categoria_id' => $categoria])))->toBe('23505')
        ->and(plantelRechazo(fn () => plantelEquipo(['nombre' => 'Lobos', 'categoria_id' => plantelCategoria('Sub-19')])))->toBeNull();

    DB::connection('propietario')->table('equipos')->where('id', $primero)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(plantelRechazo(fn () => plantelEquipo(['nombre' => 'Lobos', 'categoria_id' => $categoria])))->toBeNull();
});

test('RNF-03: posiciones.codigo solo admite las cuatro posiciones y es unico', function () {
    expect(plantelRechazo(fn () => plantelPosicion('portero')))->toBeNull()
        ->and(plantelRechazo(fn () => plantelPosicion('portero')))->toBe('23505')
        ->and(plantelRechazo(fn () => plantelPosicion('lateral')))->toBe('23514');
});

test('RN-17: dos membresias vigentes del mismo jugador en el mismo equipo se rechazan', function () {
    $jugador = plantelJugador();
    $equipo = plantelEquipo();
    $posicion = plantelPosicion();
    $asignar = fn (array $extra = []) => plantelInsertar('jugador_equipo', [
        'jugador_id' => $jugador, 'equipo_id' => $equipo, 'posicion_id' => $posicion, 'desde' => '2026-01-10', ...$extra,
    ]);

    $vigente = $asignar();

    expect(plantelRechazo($asignar))->toBe('23505');

    DB::connection('propietario')->table('jugador_equipo')->where('id', $vigente)->update(['hasta' => '2026-02-01']);

    expect(plantelRechazo($asignar))->toBeNull()
        ->and(plantelRechazo(fn () => $asignar(['hasta' => '2026-03-01'])))->toBeNull();
});

test('RN-22: dos asignaciones vigentes del mismo entrenador al mismo equipo se rechazan', function () {
    $usuario = plantelUsuario();
    $equipo = plantelEquipo();
    $asignar = fn (array $extra = []) => plantelInsertar('entrenador_equipo', [
        'usuario_id' => $usuario, 'equipo_id' => $equipo, 'desde' => '2026-01-10', ...$extra,
    ]);

    $vigente = $asignar();

    expect(plantelRechazo($asignar))->toBe('23505');

    DB::connection('propietario')->table('entrenador_equipo')->where('id', $vigente)->update(['hasta' => '2026-02-01']);

    expect(plantelRechazo($asignar))->toBeNull();
});

test('RN-23: bajas_jugador.motivo vacio se rechaza', function () {
    $jugador = plantelJugador();
    $usuario = plantelUsuario();
    $baja = fn (string $motivo) => plantelInsertar('bajas_jugador', [
        'jugador_id' => $jugador, 'motivo' => $motivo, 'fecha' => '2026-03-15', 'registrada_por' => $usuario,
    ]);

    expect(plantelRechazo(fn () => $baja('')))->toBe('23514')
        ->and(plantelRechazo(fn () => $baja('   ')))->toBe('23514')
        ->and(plantelRechazo(fn () => $baja('Cambio de ciudad')))->toBeNull();
});

test('RNF-03: las claves foraneas de Plantel no borran en cascada', function () {
    $jugador = plantelJugador();
    $equipo = plantelEquipo();
    plantelInsertar('jugador_equipo', [
        'jugador_id' => $jugador, 'equipo_id' => $equipo, 'posicion_id' => plantelPosicion(), 'desde' => '2026-01-10',
    ]);
    $categoria = DB::connection('propietario')->table('equipos')->where('id', $equipo)->value('categoria_id');

    expect(plantelRechazo(fn () => DB::connection('propietario')->table('jugadores')->where('id', $jugador)->delete()))->toBe('23503')
        ->and(plantelRechazo(fn () => DB::connection('propietario')->table('equipos')->where('id', $equipo)->delete()))->toBe('23503')
        ->and(plantelRechazo(fn () => DB::connection('propietario')->table('categorias')->where('id', $categoria)->delete()))->toBe('23503');
});

test('RNF-02: cada tabla de historial de Plantel es de solo insercion', function () {
    $entidades = [
        'jugadores_historial' => plantelJugador(),
        'equipos_historial' => plantelEquipo(),
        'jugador_equipo_historial' => plantelInsertar('jugador_equipo', [
            'jugador_id' => plantelJugador(['matricula' => 'M-900']), 'equipo_id' => plantelEquipo(['nombre' => 'Otro']),
            'posicion_id' => plantelPosicion(), 'desde' => '2026-01-10',
        ]),
        'entrenador_equipo_historial' => plantelInsertar('entrenador_equipo', [
            'usuario_id' => plantelUsuario(), 'equipo_id' => plantelEquipo(['nombre' => 'Tercero']), 'desde' => '2026-01-10',
        ]),
        'bajas_jugador_historial' => plantelInsertar('bajas_jugador', [
            'jugador_id' => plantelJugador(['matricula' => 'M-901']), 'motivo' => 'Cambio de ciudad',
            'fecha' => '2026-03-15', 'registrada_por' => plantelUsuario(),
        ]),
    ];

    foreach (HISTORIALES_DE_PLANTEL as $tabla => $columna) {
        DB::connection('propietario')->table($tabla)->insert([
            'id' => Identificador::generar()->valor(),
            $columna => $entidades[$tabla],
            'version' => 1,
            'operacion' => 'crear',
            'datos_nuevos' => json_encode(['dato' => 'x']),
            'usuario_id' => null,
            'ocurrido_en' => '2026-03-15 10:30:00+00',
        ]);

        expect(plantelRechazo(fn () => DB::table($tabla)->update(['motivo' => 'x'])))->not->toBeNull("UPDATE sobre {$tabla}")
            ->and(plantelRechazo(fn () => DB::table($tabla)->delete()))->not->toBeNull("DELETE sobre {$tabla}")
            ->and(DB::connection('propietario')->table($tabla)->count())->toBe(1);
    }
});

test('RNF-02: categorias y posiciones no llevan historial y las demas tablas de Plantel si', function () {
    expect(Schema::connection('propietario')->hasTable('categorias_historial'))->toBeFalse()
        ->and(Schema::connection('propietario')->hasTable('posiciones_historial'))->toBeFalse();

    foreach (HISTORIALES_DE_PLANTEL as $tabla => $columna) {
        expect(Schema::connection('propietario')->hasColumn($tabla, $columna))->toBeTrue();
    }
});

test('RNF-03: migrate:rollback de Plantel no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = [...TABLAS_DE_PLANTEL, ...array_keys(HISTORIALES_DE_PLANTEL)];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse($tabla);
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue($tabla);
    }
});

test('RNF-03: PosicionesSeeder dos veces no duplica filas', function () {
    consolaArtisan('db:seed', ['--class' => 'PosicionesSeeder', '--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--class' => 'PosicionesSeeder', '--force' => true])->assertSuccessful();

    $codigos = DB::connection('propietario')->table('posiciones')->orderBy('codigo')->pluck('codigo')->all();

    expect($codigos)->toBe(['defensa', 'delantero', 'medio', 'portero']);
});

test('RNF-03: DatabaseSeeder siembra roles y posiciones', function () {
    consolaArtisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(DB::connection('propietario')->table('roles')->count())->toBe(2)
        ->and(DB::connection('propietario')->table('posiciones')->count())->toBe(4);
});
