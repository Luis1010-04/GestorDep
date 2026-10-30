<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de Competencia cumplen docs/api/openapi.yaml (como en H1 y H2). */
beforeEach(function () {
    sesionesPrepararBase();
    sesionesCrearUsuario('e1@x.com', ['entrenador']);
    sesionesCrearUsuario('d@x.com', ['docente']);
});

test('HU-28: las respuestas de los catálogos de Competencia cumplen el contrato', function (string $ruta, array $cuerpo, array $cuerpoInvalido) {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    if (isset($cuerpo['temporada_id'])) {
        $cuerpo['temporada_id'] = mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);
    }
    $api = '/api/v1'.$ruta;

    $creado = sesionesPeticion($this, 'POST', $api, $cuerpo, $sesion)->assertCreated();
    $id = $creado->json('datos.id');
    $respuestas = [
        [$ruta, 'post', $creado],
        [$ruta, 'post', sesionesPeticion($this, 'POST', $api, $cuerpo, $sesion)->assertStatus(409)],
        [$ruta, 'post', sesionesPeticion($this, 'POST', $api, $cuerpoInvalido, $sesion)->assertUnprocessable()],
        [$ruta, 'post', sesionesPeticion($this, 'POST', $api, $cuerpo)->assertUnauthorized()],
        [$ruta, 'post', sesionesPeticion($this, 'POST', $api, $cuerpo, sesionesIniciar($this, 'd@x.com'))->assertForbidden()],
        [$ruta, 'get', sesionesPeticion($this, 'GET', $api, idDeSesion: $sesion)->assertOk()],
        [$ruta, 'get', sesionesPeticion($this, 'GET', $api.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        [$ruta, 'get', sesionesPeticion($this, 'GET', $api)->assertUnauthorized()],
        [$ruta, 'get', sesionesPeticion($this, 'GET', $api, idDeSesion: sesionesIniciar($this, 'd@x.com'))->assertForbidden()],
        ["$ruta/{id}", 'delete', sesionesPeticion($this, 'DELETE', "$api/$id", idDeSesion: $sesion)->assertNoContent()],
        ["$ruta/{id}", 'delete', sesionesPeticion($this, 'DELETE', "$api/$id", idDeSesion: $sesion)->assertNotFound()],
        ["$ruta/{id}", 'delete', sesionesPeticion($this, 'DELETE', "$api/".Identificador::generar()->valor())->assertUnauthorized()],
        ["$ruta/{id}", 'delete', sesionesPeticion($this, 'DELETE', "$api/".Identificador::generar()->valor(), idDeSesion: sesionesIniciar($this, 'd@x.com'))->assertForbidden()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        $plantilla = str_replace('{id}', ['/lugares' => '{lugar}', '/rivales' => '{rival}', '/temporadas' => '{temporada}', '/torneos' => '{torneo}'][$ruta], $plantilla);
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
})->with([
    'lugares' => ['/lugares', ['nombre' => 'Cancha sur'], ['nombre' => '']],
    'rivales' => ['/rivales', ['nombre' => 'Halcones'], []],
    'temporadas' => ['/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2027-01-01', 'fecha_fin' => '2027-06-30'], ['nombre' => 'T3', 'fecha_inicio' => '2028-02-01', 'fecha_fin' => '2028-01-01']],
    'torneos' => ['/torneos', ['nombre' => 'Copa', 'temporada_id' => '{t}'], ['nombre' => 'Copa', 'temporada_id' => Identificador::generar()->valor()]],
]);

test('DD-07: el 409 temporada_solapada cumple el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    mundoInsertar('temporadas', ['nombre' => 'T1', 'fecha_inicio' => '2026-08-01', 'fecha_fin' => '2026-12-15']);

    $respuesta = sesionesPeticion($this, 'POST', '/api/v1/temporadas', ['nombre' => 'T2', 'fecha_inicio' => '2026-12-10', 'fecha_fin' => '2026-12-20'], sesionesIniciar($this, 'e1@x.com'))
        ->assertStatus(409);

    expect($validador->validate($respuesta->baseResponse, '/temporadas', 'post'))->toBeTrue();
});
