<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

beforeEach(function () {
    sesionesPrepararBase();

    Route::middleware(['api', 'auth:web', 'rol:entrenador'])->get('/api/v1/prueba/entrenador', fn () => ['funcion' => 'entrenador']);
    Route::middleware(['api', 'auth:web', 'rol:docente'])->get('/api/v1/prueba/docente', fn () => ['funcion' => 'docente']);
    Route::middleware(['api', 'auth:web', 'rol:entrenador,docente'])->get('/api/v1/prueba/cualquiera', fn () => ['funcion' => 'cualquiera']);
    Route::middleware(['api', 'rol:entrenador'])->get('/api/v1/prueba/sin-auth', fn () => ['funcion' => 'sin-auth']);
});

test('HU-01: un docente recibe 403 en una función de entrenador', function () {
    /** @var TestCase $this */
    sesionesCrearUsuario('d@x.com', ['docente']);
    $sesion = sesionesIniciar($this, 'd@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/prueba/entrenador', idDeSesion: $sesion)
        ->assertForbidden()
        ->assertExactJson([
            'codigo' => 'sin_permiso',
            'mensaje' => 'No tiene permiso para realizar esta acción.',
            'errores' => [],
        ]);
    sesionesPeticion($this, 'GET', '/api/v1/prueba/docente', idDeSesion: $sesion)->assertOk();
});

test('HU-01: un usuario con los dos roles accede a ambas', function () {
    /** @var TestCase $this */
    sesionesCrearUsuario('ad@x.com', ['docente', 'entrenador']);
    $sesion = sesionesIniciar($this, 'ad@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/prueba/entrenador', idDeSesion: $sesion)->assertOk()->assertJsonPath('funcion', 'entrenador');
    sesionesPeticion($this, 'GET', '/api/v1/prueba/docente', idDeSesion: $sesion)->assertOk()->assertJsonPath('funcion', 'docente');
});

test('HU-01: un entrenador recibe 403 en una función de docente', function () {
    /** @var TestCase $this */
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    $sesion = sesionesIniciar($this, 'a@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/prueba/docente', idDeSesion: $sesion)->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
});

test('HU-01: varios usuarios con sesión propia en una misma prueba conservan cada uno su rol', function () {
    /** @var TestCase $this */
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
    $sesionA = sesionesIniciar($this, 'a@x.com');
    $sesionD = sesionesIniciar($this, 'd@x.com');

    sesionesPeticion($this, 'GET', '/api/v1/prueba/entrenador', idDeSesion: $sesionA)->assertOk();
    sesionesPeticion($this, 'GET', '/api/v1/prueba/entrenador', idDeSesion: $sesionD)->assertForbidden();
    sesionesPeticion($this, 'GET', '/api/v1/prueba/entrenador', idDeSesion: $sesionA)->assertOk();
});

test('HU-01: rol con varios roles deja pasar a quien tenga cualquiera de ellos', function () {
    /** @var TestCase $this */
    sesionesCrearUsuario('a@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);

    sesionesPeticion($this, 'GET', '/api/v1/prueba/cualquiera', idDeSesion: sesionesIniciar($this, 'a@x.com'))->assertOk();
    sesionesPeticion($this, 'GET', '/api/v1/prueba/cualquiera', idDeSesion: sesionesIniciar($this, 'd@x.com'))->assertOk();
});

test('HU-01: sin sesión el control de rol responde 401', function () {
    /** @var TestCase $this */
    sesionesPeticion($this, 'GET', '/api/v1/prueba/sin-auth')
        ->assertUnauthorized()
        ->assertJsonPath('codigo', 'no_autenticado');
});
