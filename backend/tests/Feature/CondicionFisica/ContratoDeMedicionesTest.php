<?php

use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de mediciones cumplen docs/api/openapi.yaml. */
test('HU-17: las respuestas de mediciones cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = medicionesMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $ruta = '/jugadores/{jugador}/mediciones';

    $respuestas = [
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $e1)->assertCreated()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '71.00', 'es_correccion' => true, 'motivo' => 'Error']), $e1)->assertOk()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $e1)->assertConflict()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['jb']), medicionCuerpo($m['a']), $e1)->assertConflict()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), [], $e1)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['b']), $e1)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['peso_kg' => '1.00']), $e1)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a'], ['fecha' => '2026-10-05', 'es_correccion' => true, 'motivo' => 'Error']), $e1)->assertUnprocessable()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']))->assertUnauthorized()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $d1)->assertForbidden()],
        ['post', sesionesPeticion($this, 'POST', medicionesRuta($m['j1']), medicionCuerpo($m['a']), $e2)->assertNotFound()],
        ['get', sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        ['get', sesionesPeticion($this, 'GET', medicionesRuta($m['f1']))->assertUnauthorized()],
        ['get', sesionesPeticion($this, 'GET', medicionesRuta($m['f1']), idDeSesion: $d1)->assertForbidden()],
        ['get', sesionesPeticion($this, 'GET', medicionesRuta($m['j1']), idDeSesion: $e2)->assertNotFound()],
        ['get', sesionesPeticion($this, 'GET', medicionesRuta($m['f1']).'?pagina=0', idDeSesion: $e1)->assertUnprocessable()],
    ];

    foreach ($respuestas as [$metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue();
    }
});

test('DA-70: el 422 rango_no_configurado de mediciones cumple el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = medicionesMundo();
    DB::connection('propietario')->table('rangos_mediciones_corporales')->where('codigo', 'peso_kg')->delete();

    $respuesta = sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), sesionesIniciar($this, 'e1@x.com'))
        ->assertUnprocessable()
        ->assertJsonPath('codigo', 'rango_no_configurado');

    expect($validador->validate($respuesta->baseResponse, '/jugadores/{jugador}/mediciones', 'post'))->toBeTrue();
});
