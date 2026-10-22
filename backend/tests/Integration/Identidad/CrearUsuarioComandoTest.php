<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\artisan;

const CONTRASENA_VALIDA = 'una-clave-larga-1';

beforeEach(function () {
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    (new RolesSeeder)->run();
});

test('CONSOLA usuarios:crear: un rol y contraseña por opción crea el usuario', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana Pérez', '--rol' => ['entrenador'], '--contrasena' => CONTRASENA_VALIDA])
        ->assertSuccessful();

    $usuario = DB::table('usuarios')->where('correo', 'ana@example.test')->first();

    expect($usuario->nombre)->toBe('Ana Pérez')
        ->and($usuario->activo)->toBeTrue()
        ->and(Hash::check(CONTRASENA_VALIDA, $usuario->contrasena_hash))->toBeTrue()
        ->and(DB::table('usuario_rol')->where('usuario_id', $usuario->id)->count())->toBe(1);
});

test('CONSOLA usuarios:crear: dos roles crean dos filas en usuario_rol', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente', 'entrenador'], '--contrasena' => CONTRASENA_VALIDA])
        ->assertSuccessful();

    $codigos = DB::table('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')->orderBy('roles.codigo')->pluck('roles.codigo')->all();

    expect($codigos)->toBe(['docente', 'entrenador']);
});

test('CONSOLA usuarios:crear: sin contraseña por opción la pide por consola sin eco', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente']])
        ->expectsQuestion(__('identidad.consola.pedir_contrasena'), CONTRASENA_VALIDA)
        ->assertSuccessful();

    expect(Hash::check(CONTRASENA_VALIDA, (string) DB::table('usuarios')->value('contrasena_hash')))->toBeTrue();
});

test('CONSOLA usuarios:crear: un correo ya existente, con otras mayúsculas, falla y no crea nada', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente'], '--contrasena' => CONTRASENA_VALIDA])->assertSuccessful();

    artisan('usuarios:crear', ['correo' => 'ANA@example.test', 'nombre' => 'Otra', '--rol' => ['entrenador'], '--contrasena' => CONTRASENA_VALIDA])
        ->expectsOutputToContain(__('errores.reglas.correo_duplicado'))
        ->assertFailed();

    expect(DB::table('usuarios')->count())->toBe(1)
        ->and(DB::table('usuario_rol')->count())->toBe(1)
        ->and(DB::table('usuarios_historial')->count())->toBe(1);
});

test('CONSOLA usuarios:crear: un rol inexistente falla y no crea nada', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente', 'administrador'], '--contrasena' => CONTRASENA_VALIDA])
        ->expectsOutputToContain(__('errores.reglas.rol_inexistente'))
        ->assertFailed();

    expect(DB::table('usuarios')->count())->toBe(0)
        ->and(DB::table('usuario_rol')->count())->toBe(0)
        ->and(DB::table('usuarios_historial')->count())->toBe(0);
});

test('CONSOLA usuarios:crear: sin --rol falla y no crea nada', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--contrasena' => CONTRASENA_VALIDA])
        ->expectsOutputToContain(__('errores.reglas.rol_obligatorio'))
        ->assertFailed();

    expect(DB::table('usuarios')->count())->toBe(0)
        ->and(DB::table('usuarios_historial')->count())->toBe(0);
});

test('CONSOLA usuarios:crear: una contraseña de 9 caracteres falla y no crea nada', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente'], '--contrasena' => '123456789'])
        ->expectsOutputToContain(__('errores.reglas.contrasena_muy_corta'))
        ->assertFailed();

    expect(DB::table('usuarios')->count())->toBe(0);
});

test('CONSOLA usuarios:crear: la salida no contiene la contraseña ni queda en claro en ninguna tabla', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente'], '--contrasena' => CONTRASENA_VALIDA])
        ->doesntExpectOutputToContain(CONTRASENA_VALIDA)
        ->assertSuccessful();

    artisan('usuarios:crear', ['correo' => 'otra@example.test', 'nombre' => 'Otra', '--rol' => ['docente'], '--contrasena' => '123456789'])
        ->doesntExpectOutputToContain('123456789')
        ->assertFailed();

    foreach (['usuarios', 'usuarios_historial', 'usuario_rol', 'roles'] as $tabla) {
        expect(json_encode(DB::table($tabla)->get()))->not->toContain(CONTRASENA_VALIDA);
    }
});

test('CONSOLA usuarios:crear: el alta escribe una fila crear en el historial sin autor y sin hash', function () {
    artisan('usuarios:crear', ['correo' => 'ana@example.test', 'nombre' => 'Ana', '--rol' => ['docente'], '--contrasena' => CONTRASENA_VALIDA])->assertSuccessful();

    $usuario = DB::table('usuarios')->first();
    $historial = DB::table('usuarios_historial')->get();

    expect($historial)->toHaveCount(1)
        ->and($historial[0]->operacion)->toBe('crear')
        ->and($historial[0]->usuario_afectado_id)->toBe($usuario->id)
        ->and($historial[0]->usuario_id)->toBeNull()
        ->and($historial[0]->datos_anteriores)->toBeNull()
        ->and(json_decode($historial[0]->datos_nuevos, true))->toEqualCanonicalizing(['nombre' => 'Ana', 'correo' => 'ana@example.test', 'activo' => true, 'roles' => ['docente']])
        ->and($historial[0]->datos_nuevos)->not->toContain('hash')
        ->and($historial[0]->datos_nuevos)->not->toContain('intentos')
        ->and($usuario->creado_por)->toBeNull();
});
