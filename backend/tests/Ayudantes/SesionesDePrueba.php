<?php

/*
 * Varios usuarios con sesion real dentro de una misma prueba. Dentro de una prueba el guard y el almacen de
 * sesion sobreviven entre peticiones; en produccion cada peticion los crea de nuevo. Cada peticion olvida
 * ambos y fija la cookie de la sesion que se indica.
 */

use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Identificador;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

use function Pest\Laravel\artisan;

const CLAVE_DE_SESIONES_DE_PRUEBA = 'una-clave-larga-1';

/** Reconstruye la base con los roles de fabrica y la sesion en la tabla `sessions`. */
function sesionesPrepararBase(): void
{
    artisan('migrate:fresh', ['--database' => 'propietario', '--force' => true])->assertSuccessful();
    (new RolesSeeder)->run();
    config(['session.driver' => 'database']);
}

/**
 * @param  list<string>  $roles
 * @return string identificador del usuario
 */
function sesionesCrearUsuario(string $correo, array $roles): string
{
    $id = Identificador::generar()->valor();
    DB::connection('propietario')->table('usuarios')->insert([
        'id' => $id,
        'nombre' => ucfirst(strstr($correo, '@', true) ?: $correo),
        'correo' => $correo,
        'contrasena_hash' => Hash::make(CLAVE_DE_SESIONES_DE_PRUEBA),
        'activo' => true,
    ]);
    foreach ($roles as $rol) {
        DB::connection('propietario')->table('usuario_rol')->insert([
            'usuario_id' => $id,
            'rol_id' => DB::table('roles')->where('codigo', $rol)->value('id'),
        ]);
    }

    return $id;
}

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function sesionesPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = [], ?string $idDeSesion = null): TestResponse
{
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    (fn () => $this->defaultCookies = $idDeSesion === null ? [] : [config('session.cookie') => $idDeSesion])->call($prueba);

    return $prueba->withCredentials()->withHeaders(['Origin' => 'http://localhost'])->json($metodo, $ruta, $cuerpo);
}

/** Inicia sesion con el usuario creado por `sesionesCrearUsuario` y devuelve el identificador de su sesion. */
function sesionesIniciar(TestCase $prueba, string $correo): string
{
    $antes = DB::table('sessions')->pluck('id')->all();
    sesionesPeticion($prueba, 'POST', '/api/v1/sesion', ['correo' => $correo, 'contrasena' => CLAVE_DE_SESIONES_DE_PRUEBA])->assertOk();

    return (string) DB::table('sessions')->whereNotNull('user_id')->whereNotIn('id', $antes)->value('id');
}
