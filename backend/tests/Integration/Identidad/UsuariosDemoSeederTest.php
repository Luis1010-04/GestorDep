<?php

use Database\Seeders\RolesSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
});

test('CONSOLA: UsuariosDemoSeeder se niega a correr en producción', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new UsuariosDemoSeeder)->run())->toThrow(RuntimeException::class, 'Los usuarios de demostración no se pueden crear con APP_ENV=production.');

    expect(DB::table('usuarios')->count())->toBe(0);
});

test('CONSOLA: UsuariosDemoSeeder crea un docente y dos entrenadores y es idempotente', function () {
    (new UsuariosDemoSeeder)->run();
    (new UsuariosDemoSeeder)->run();

    $roles = DB::table('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')->pluck('roles.codigo')->sort()->values()->all();
    $usuario = DB::table('usuarios')->where('correo', UsuariosDemoSeeder::CORREO_ENTRENADOR_1)->first();

    expect(DB::table('usuarios')->count())->toBe(3)
        ->and($roles)->toBe(['docente', 'entrenador', 'entrenador'])
        ->and(DB::table('usuarios_historial')->count())->toBe(3)
        ->and(Hash::check(UsuariosDemoSeeder::contrasena(), $usuario->contrasena_hash))->toBeTrue()
        ->and(strlen(UsuariosDemoSeeder::contrasena()))->toBeGreaterThanOrEqual(10);
});
