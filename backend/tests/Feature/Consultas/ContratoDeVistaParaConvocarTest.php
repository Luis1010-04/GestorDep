<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las dos rutas de la vista para convocar cumplen docs/api/openapi.yaml (HU-24, RN-18, DA-94, DA-119). */
test('HU-24 (DA-94, DA-119): las respuestas de la vista para convocar y de la lista del equipo cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = convocarMundo();
    convocarEvaluar($m['f1'], $m['a'], $m['e1'], 'pasa', '8.00');
    convocarMedir($m['f1'], $m['a'], $m['e1'], '2026-09-20');
    convocarAsistir($m, $m['a'], $m['f1'], 'presente', $m['e1']);
    estadoInsertar($m['p1'], $m['e1'], 'lesionado');
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e3 = sesionesIniciar($this, 'e3@x.com');
    $d = sesionesIniciar($this, 'd@x.com');
    $vista = '/jugadores/{jugador}/vista-para-convocar';
    $lista = '/equipos/{equipo}/jugadores/para-convocar';
    $inexistente = Identificador::generar()->valor();

    $respuestas = [
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['p1']), idDeSesion: $e1)->assertOk()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['f1'], "filtro[equipo_id]={$m['b']}"), idDeSesion: $e3)->assertOk()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['f1']))->assertUnauthorized()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['f1']), idDeSesion: $d)->assertForbidden()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($inexistente), idDeSesion: $e1)->assertNotFound()],
        [$vista, 'get', sesionesPeticion($this, 'GET', convocarRuta($m['f1'], 'filtro[equipo_id]=abc'), idDeSesion: $e1)->assertUnprocessable()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a']), idDeSesion: $e1)->assertOk()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a'], 'por_pagina=2&pagina=2'), idDeSesion: $e1)->assertOk()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a']))->assertUnauthorized()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a']), idDeSesion: $d)->assertForbidden()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['b']), idDeSesion: $e1)->assertNotFound()],
        [$lista, 'get', sesionesPeticion($this, 'GET', convocarRutaDelEquipo($m['a'], 'por_pagina=500'), idDeSesion: $e1)->assertUnprocessable()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
