<?php

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    (new RolesSeeder)->run();
});

/** @param  list<string>  $roles */
function crearUsuarioPersistido(string $correo, array $roles = ['docente']): UsuarioModelo
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id, 'nombre' => 'Ana', 'correo' => $correo, 'contrasena_hash' => 'hash',
    ]);
    foreach ($roles as $codigo) {
        DB::connection('propietario')->table('usuario_rol')->insert([
            'usuario_id' => $id,
            'rol_id' => DB::table('roles')->where('codigo', $codigo)->value('id'),
        ]);
    }

    return UsuarioModelo::findOrFail($id);
}

test('HU-01: sin sesión no hay usuario actual', function () {
    expect(app(UsuarioActual::class)->identificador())->toBeNull();
});

test('HU-01: con sesión el usuario actual expone su identificador', function () {
    $usuario = crearUsuarioPersistido('ana@example.test');
    Auth::guard('web')->login($usuario);

    expect(app(UsuarioActual::class)->identificador()?->valor())->toBe($usuario->id);
});

test('HU-01: el usuario actual expone sus roles', function () {
    Auth::guard('web')->login(crearUsuarioPersistido('ana@example.test', ['entrenador', 'docente']));

    expect(app(UsuarioActual::class)->roles())->toBe(['docente', 'entrenador']);
});

test('HU-01: sin sesión el usuario actual no tiene roles', function () {
    expect(app(UsuarioActual::class)->roles())->toBe([]);
});

test('RNF-10: el modelo expone contrasena_hash como contraseña de autenticación', function () {
    expect(crearUsuarioPersistido('ana@example.test')->getAuthPassword())->toBe('hash');
});

test('RNF-03: un usuario con borrado lógico no se recupera para autenticar', function () {
    $usuario = crearUsuarioPersistido('ana@example.test');
    DB::connection('propietario')->table('usuarios')->where('id', $usuario->id)->update(['eliminado_en' => '2026-03-15 10:30:00+00']);

    expect(UsuarioModelo::find($usuario->id))->toBeNull();
});
