<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de tipos de prueba y pruebas físicas cumplen docs/api/openapi.yaml. */
test('HU-18: las respuestas de tipos de prueba y pruebas físicas cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = pruebasMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $pruebas = '/jugadores/{jugador}/pruebas-fisicas';
    $tipos = '/tipos-prueba';

    $respuestas = [
        [$tipos, 'get', sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba', idDeSesion: $e1)->assertOk()],
        [$tipos, 'get', sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba')->assertUnauthorized()],
        [$tipos, 'get', sesionesPeticion($this, 'GET', '/api/v1/tipos-prueba', idDeSesion: $d1)->assertForbidden()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $e1)->assertCreated()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $e1)->assertConflict()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad'], ['resultado' => '4.20', 'es_correccion' => true, 'motivo' => 'Error']), $e1)->assertOk()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['jb']), pruebaCuerpo($m['a'], $m['velocidad']), $e1)->assertConflict()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), [], $e1)->assertUnprocessable()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['resistencia'], ['resultado' => '6000.01']), $e1)->assertUnprocessable()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['f1']), $e1)->assertUnprocessable()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']))->assertUnauthorized()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $d1)->assertForbidden()],
        [$pruebas, 'post', sesionesPeticion($this, 'POST', pruebasRuta($m['j1']), pruebaCuerpo($m['a'], $m['velocidad']), $e2)->assertNotFound()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-11', idDeSesion: $e1)->assertOk()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['f1']).'?filtro[periodo]=2026-13', idDeSesion: $e1)->assertUnprocessable()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['f1']))->assertUnauthorized()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['f1']), idDeSesion: $d1)->assertForbidden()],
        [$pruebas, 'get', sesionesPeticion($this, 'GET', pruebasRuta($m['j1']), idDeSesion: $e2)->assertNotFound()],
    ];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue();
    }
});
