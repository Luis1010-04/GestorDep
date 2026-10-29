<?php

use Database\Seeders\RolesSeeder;
use Database\Seeders\UsuariosDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    esquemaReiniciar();
    (new RolesSeeder)->run();
});

test('CONSOLA (F1-H2-T15): db:seed --class=UsuariosDemoSeeder crea el segundo docente solo con el rol docente, distinto del primero', function () {
    consolaArtisan('db:seed', ['--class' => 'UsuariosDemoSeeder', '--force' => true])->assertSuccessful();
    consolaArtisan('db:seed', ['--class' => 'UsuariosDemoSeeder', '--force' => true])->assertSuccessful();

    $rolesDe = fn (string $correo): array => DB::table('usuarios')
        ->join('usuario_rol', 'usuario_rol.usuario_id', '=', 'usuarios.id')
        ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
        ->where('usuarios.correo', $correo)
        ->pluck('roles.codigo')->all();

    expect(UsuariosDemoSeeder::CORREO_DOCENTE_2)->not->toBe(UsuariosDemoSeeder::CORREO_DOCENTE)
        ->and(DB::table('usuarios')->where('correo', UsuariosDemoSeeder::CORREO_DOCENTE_2)->count())->toBe(1)
        ->and($rolesDe(UsuariosDemoSeeder::CORREO_DOCENTE_2))->toBe(['docente'])
        ->and($rolesDe(UsuariosDemoSeeder::CORREO_DOCENTE))->toBe(['docente']);
});

test('CONSOLA: UsuariosDemoSeeder se niega a correr en producción', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new UsuariosDemoSeeder)->run())->toThrow(RuntimeException::class, 'Los usuarios de demostración no se pueden crear con APP_ENV=production.');

    expect(DB::table('usuarios')->count())->toBe(0);
});

test('CONSOLA: UsuariosDemoSeeder crea dos docentes y dos entrenadores y es idempotente', function () {
    (new UsuariosDemoSeeder)->run();
    (new UsuariosDemoSeeder)->run();

    $roles = DB::table('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')->pluck('roles.codigo')->sort()->values()->all();
    $usuario = DB::table('usuarios')->where('correo', UsuariosDemoSeeder::CORREO_ENTRENADOR_1)->first();

    expect(DB::table('usuarios')->count())->toBe(4)
        ->and($roles)->toBe(['docente', 'docente', 'entrenador', 'entrenador'])
        ->and(DB::table('usuarios_historial')->count())->toBe(4)
        ->and(Hash::check(UsuariosDemoSeeder::contrasena(), $usuario->contrasena_hash))->toBeTrue()
        ->and(strlen(UsuariosDemoSeeder::contrasena()))->toBeGreaterThanOrEqual(10);
});
