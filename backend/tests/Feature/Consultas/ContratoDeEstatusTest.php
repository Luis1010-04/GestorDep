<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las alertas del jugador y del listado de estatus cumplen docs/api/openapi.yaml (HU-22, HU-23). */
test('HU-22, HU-23 (DA-105): las respuestas de las alertas y del listado de estatus cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = estatusMundo();
    alertaInsertar($m['f1'], ALERTA_ACADEMICA, causa: ['nivel_riesgo' => 'en_riesgo', 'promedio' => '6.50', 'materias_reprobadas' => 1]);
    alertaInsertar($m['f1'], ALERTA_DEPORTIVA, $m['a'], ['resultado' => 'no_pasa', 'promedio' => '5.00', 'acumulacion_sanciones' => false]);
    alertaInsertar($m['j1'], ALERTA_SANCIONES, $m['a'], ['tarjetas_amarillas' => 3, 'tarjetas_rojas' => 0, 'faltas_en_ventana' => 0]);
    estadoInsertar($m['f1'], $m['e2']);
    avisoInsertar($m['j1'], $m['e1']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $de = sesionesIniciar($this, 'de@x.com');
    $alertas = '/jugadores/{jugador}/alertas';
    $estatus = '/estatus-de-jugadores';
    $inexistente = Identificador::generar()->valor();

    $respuestas = [
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $d1)->assertOk()],
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($m['j1']), idDeSesion: $e1)->assertOk()],
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($m['f1']))->assertUnauthorized()],
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($m['jb']), idDeSesion: $e1)->assertNotFound()],
        [$alertas, 'get', sesionesPeticion($this, 'GET', alertasRuta($inexistente), idDeSesion: $e1)->assertNotFound()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $e1)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $d1)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $de)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta('por_pagina=1&pagina=2'), idDeSesion: $e1)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]='.ALERTA_DEPORTIVA), idDeSesion: $e1)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta("filtro[equipo_id]=$inexistente"), idDeSesion: $e1)->assertOk()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta())->assertUnauthorized()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta('filtro[tipo_alerta]=otra'), idDeSesion: $e1)->assertUnprocessable()],
        [$estatus, 'get', sesionesPeticion($this, 'GET', estatusRuta('por_pagina=0'), idDeSesion: $e1)->assertUnprocessable()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
