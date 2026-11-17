<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de la vista consolidada de la convocatoria cumplen docs/api/openapi.yaml (HU-12 c2, DA-96). */
test('HU-12 c2 (DA-96): las respuestas de GET /partidos/{partido}/convocatoria/vista cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = convocarMundo();
    convocarEvaluar($m['p1'], $m['a'], $m['e1'], 'pasa', '8.00');
    convocarMedir($m['d1'], $m['a'], $m['e1'], '2026-09-20');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d = sesionesIniciar($this, 'd@x.com');
    $plantilla = '/partidos/{partido}/convocatoria/vista';
    $ruta = fn (string $partido, string $consulta = ''): string => "/api/v1/partidos/$partido/convocatoria/vista".($consulta === '' ? '' : "?$consulta");

    $respuestas = [
        sesionesPeticion($this, 'GET', $ruta($m['pj1']), idDeSesion: $e1)->assertOk(),
        sesionesPeticion($this, 'GET', $ruta($m['pj1'], 'por_pagina=2&pagina=2'), idDeSesion: $e1)->assertOk(),
        sesionesPeticion($this, 'GET', $ruta($m['pp1']), idDeSesion: $e1)->assertOk(),
        sesionesPeticion($this, 'GET', $ruta($m['pj1']))->assertUnauthorized(),
        sesionesPeticion($this, 'GET', $ruta($m['pj1']), idDeSesion: $d)->assertForbidden(),
        sesionesPeticion($this, 'GET', $ruta(Identificador::generar()->valor()), idDeSesion: $e1)->assertNotFound(),
        sesionesPeticion($this, 'GET', $ruta($m['pj1'], 'por_pagina=500'), idDeSesion: $e1)->assertUnprocessable(),
    ];

    foreach ($respuestas as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, 'get'))->toBeTrue("get $plantilla {$respuesta->getStatusCode()}");
    }
});
