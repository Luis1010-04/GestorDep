<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de captura de rendimiento cumplen docs/api/openapi.yaml (HU-14). */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-14: las respuestas de métricas, pendientes y rendimientos cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = capturaMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $lista = "/api/v1/partidos/{$m['pj1']}/rendimientos";

    $creado = sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['p1'], 'portero'), $sesion)->assertCreated();
    $corregir = '/api/v1/rendimientos/'.$creado->json('datos.id');
    $respuestas = [
        ['/metricas', 'get', sesionesPeticion($this, 'GET', '/api/v1/metricas', idDeSesion: $sesion)->assertOk()],
        ['/metricas', 'get', sesionesPeticion($this, 'GET', '/api/v1/metricas')->assertUnauthorized()],
        ['/metricas', 'get', sesionesPeticion($this, 'GET', '/api/v1/metricas', idDeSesion: $docente)->assertForbidden()],
        ['/partidos/pendientes-de-rendimiento', 'get', sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento', idDeSesion: $sesion)->assertOk()],
        ['/partidos/pendientes-de-rendimiento', 'get', sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['/partidos/pendientes-de-rendimiento', 'get', sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento')->assertUnauthorized()],
        ['/partidos/pendientes-de-rendimiento', 'get', sesionesPeticion($this, 'GET', '/api/v1/partidos/pendientes-de-rendimiento', idDeSesion: $docente)->assertForbidden()],
        ['/partidos/{partido}/rendimientos', 'post', $creado],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['p1'], 'portero'), $sesion)->assertStatus(409)],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', "/api/v1/partidos/{$m['pp1']}/rendimientos", capturaCuerpo($m['p1'], 'portero'), $sesion)->assertStatus(409)],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['d1'], 'defensa', ['minutos' => 151]), $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, [], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['d1'], 'defensa'), $ajena)->assertNotFound()],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['d1'], 'defensa'))->assertUnauthorized()],
        ['/partidos/{partido}/rendimientos', 'post', sesionesPeticion($this, 'POST', $lista, capturaCuerpo($m['d1'], 'defensa'), $docente)->assertForbidden()],
        ['/partidos/{partido}/rendimientos', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $sesion)->assertOk()],
        ['/partidos/{partido}/rendimientos', 'get', sesionesPeticion($this, 'GET', $lista.'?por_pagina=101', idDeSesion: $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/rendimientos', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $ajena)->assertNotFound()],
        ['/partidos/{partido}/rendimientos', 'get', sesionesPeticion($this, 'GET', $lista)->assertUnauthorized()],
        ['/partidos/{partido}/rendimientos', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $docente)->assertForbidden()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', $corregir, ['calificacion_general' => 8], $sesion)->assertOk()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', $corregir, ['calificacion_general' => 11], $sesion)->assertUnprocessable()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', $corregir, ['calificacion_general' => 8], $ajena)->assertNotFound()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', '/api/v1/rendimientos/'.Identificador::generar()->valor(), ['calificacion_general' => 8], $sesion)->assertNotFound()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', $corregir, ['calificacion_general' => 8])->assertUnauthorized()],
        ['/rendimientos/{rendimiento}', 'patch', sesionesPeticion($this, 'PATCH', $corregir, ['calificacion_general' => 8], $docente)->assertForbidden()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
