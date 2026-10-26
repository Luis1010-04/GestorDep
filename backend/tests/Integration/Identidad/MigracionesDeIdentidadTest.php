<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Src\Compartido\Dominio\Identificador;

beforeEach(function () {
    esquemaReiniciar();
});

/** @param  array<string, mixed>  $atributos */
function insertarUsuario(array $atributos = []): string
{
    $id = Identificador::generar()->valor();

    DB::connection('propietario')->table('usuarios')->insert(array_merge([
        'id' => $id,
        'nombre' => 'Usuario de prueba',
        'correo' => 'prueba@example.test',
        'contrasena_hash' => 'hash-de-prueba',
    ], $atributos));

    return $id;
}

function sqlstateDeRechazo(callable $operacion): ?string
{
    try {
        $operacion();
    } catch (QueryException $e) {
        return (string) $e->getCode();
    }

    return null;
}

test('RNF-03: el correo repetido en otras mayusculas se rechaza', function () {
    insertarUsuario(['correo' => 'Entrenador@Example.test']);

    expect(sqlstateDeRechazo(fn () => insertarUsuario(['correo' => 'entrenador@example.TEST'])))->toBe('23505');
});

test('RNF-03: el indice parcial de correo permite reutilizarlo tras el borrado logico', function () {
    $primero = insertarUsuario(['correo' => 'repetido@example.test']);
    DB::connection('propietario')->table('usuarios')->where('id', $primero)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(sqlstateDeRechazo(fn () => insertarUsuario(['correo' => 'repetido@example.test'])))->toBeNull()
        ->and(sqlstateDeRechazo(fn () => insertarUsuario(['correo' => 'repetido@example.test'])))->toBe('23505');
});

test('RNF-03: usuarios aplica los valores por defecto de activo e intentos fallidos', function () {
    $id = insertarUsuario();

    $fila = DB::connection('propietario')->table('usuarios')->where('id', $id)->first();

    expect($fila->activo)->toBeTrue()
        ->and($fila->intentos_fallidos)->toBe(0)
        ->and($fila->bloqueado_hasta)->toBeNull();
});

test('RNF-03: roles.codigo solo admite docente o entrenador', function () {
    $insertar = fn (string $codigo) => DB::connection('propietario')->table('roles')->insert([
        'id' => Identificador::generar()->valor(),
        'codigo' => $codigo,
        'nombre' => $codigo,
    ]);

    expect(sqlstateDeRechazo(fn () => $insertar('docente')))->toBeNull()
        ->and(sqlstateDeRechazo(fn () => $insertar('entrenador')))->toBeNull()
        ->and(sqlstateDeRechazo(fn () => $insertar('administrador')))->toBe('23514')
        ->and(sqlstateDeRechazo(fn () => $insertar('docente')))->toBe('23505');
});

test('RNF-03: usuario_rol tiene clave compuesta y claves foraneas sin cascada', function () {
    $usuario = insertarUsuario();
    $rol = Identificador::generar()->valor();
    DB::connection('propietario')->table('roles')->insert(['id' => $rol, 'codigo' => 'docente', 'nombre' => 'Docente']);
    $asignar = fn () => DB::connection('propietario')->table('usuario_rol')->insert(['usuario_id' => $usuario, 'rol_id' => $rol]);

    expect(sqlstateDeRechazo($asignar))->toBeNull()
        ->and(sqlstateDeRechazo($asignar))->toBe('23505')
        ->and(sqlstateDeRechazo(fn () => DB::connection('propietario')->table('roles')->where('id', $rol)->delete()))->toBe('23503');
});

test('RNF-02: usuarios_historial es de solo insercion', function () {
    $usuario = insertarUsuario();
    DB::connection('propietario')->table('usuarios_historial')->insert([
        'id' => Identificador::generar()->valor(),
        'usuario_afectado_id' => $usuario,
        'version' => 1,
        'operacion' => 'crear',
        'datos_nuevos' => json_encode(['nombre' => 'Usuario de prueba']),
        'usuario_id' => null,
        'ocurrido_en' => '2026-03-15 10:30:00+00',
    ]);

    expect(sqlstateDeRechazo(fn () => DB::table('usuarios_historial')->update(['motivo' => 'x'])))->not->toBeNull()
        ->and(sqlstateDeRechazo(fn () => DB::table('usuarios_historial')->delete()))->not->toBeNull()
        ->and(DB::connection('propietario')->table('usuarios_historial')->count())->toBe(1);
});

test('RNF-02: usuarios_historial distingue la entidad afectada del autor, que admite nulo (DD-33)', function () {
    $columnas = DB::connection('propietario')->select(
        "select column_name, is_nullable from information_schema.columns
          where table_name = 'usuarios_historial' and column_name in ('usuario_id', 'usuario_afectado_id')
          order by column_name",
    );

    expect(array_map(fn ($c) => [$c->column_name, $c->is_nullable], $columnas))->toBe([
        ['usuario_afectado_id', 'NO'],
        ['usuario_id', 'YES'],
    ]);
});

test('RNF-10: sessions existe con user_id uuid nulo, indice y sin clave foranea', function () {
    $columnas = DB::connection('propietario')->select(
        "select column_name, data_type, is_nullable from information_schema.columns
          where table_name = 'sessions' order by ordinal_position",
    );
    $tipos = [];
    foreach ($columnas as $c) {
        $tipos[$c->column_name] = [$c->data_type, $c->is_nullable];
    }

    expect($tipos)->toBe([
        'id' => ['character varying', 'NO'],
        'user_id' => ['uuid', 'YES'],
        'ip_address' => ['character varying', 'YES'],
        'user_agent' => ['text', 'YES'],
        'payload' => ['text', 'NO'],
        'last_activity' => ['integer', 'NO'],
    ]);

    $claves = DB::connection('propietario')->scalar("select count(*) from pg_constraint where conrelid = 'sessions'::regclass and contype = 'f'");
    $indices = DB::connection('propietario')->scalar("select count(*) from pg_indexes where tablename = 'sessions' and indexdef like '%(user_id)%'");

    expect($claves)->toBe(0)->and($indices)->toBe(1);
});

test('RNF-10: la configuracion de sesion usa la tabla sessions y 120 minutos', function () {
    $ejemplo = (string) file_get_contents(base_path('.env.example'));

    expect(config('session.table'))->toBe('sessions')
        ->and(config('session.lifetime'))->toBe(120)
        ->and($ejemplo)->toContain("SESSION_DRIVER=database\n")
        ->and($ejemplo)->toContain("SESSION_LIFETIME=120\n");
});

test('RNF-03: migrate:rollback de Identidad y de sesion no falla y se vuelve a aplicar', function () {
    esquemaMarcarSucio();
    $tablas = ['usuarios', 'roles', 'usuario_rol', 'usuarios_historial', 'sessions'];

    consolaArtisan('migrate:rollback', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeFalse();
    }

    consolaArtisan('migrate', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    foreach ($tablas as $tabla) {
        expect(Schema::connection('propietario')->hasTable($tabla))->toBeTrue();
    }
});

test('RNF-03: RolesSeeder dos veces no duplica filas', function () {
    consolaArtisan('db:seed', ['--class' => 'RolesSeeder', '--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--class' => 'RolesSeeder', '--force' => true])->assertSuccessful();

    $codigos = DB::connection('propietario')->table('roles')->orderBy('codigo')->pluck('codigo')->all();

    expect($codigos)->toBe(['docente', 'entrenador']);
});
