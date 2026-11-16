<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de los gráficos del jugador y del equipo cumplen docs/api/openapi.yaml (HU-25, DA-105). */
test('HU-25 (DA-105): las respuestas de los gráficos del jugador y del equipo cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = graficosMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e3 = sesionesIniciar($this, 'e3@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $de = sesionesIniciar($this, 'de@x.com');
    $jugador = '/jugadores/{jugador}/graficos';
    $equipo = '/equipos/{equipo}/graficos';
    $inexistente = Identificador::generar()->valor();

    $respuestas = [
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $d1)->assertOk()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']), idDeSesion: $de)->assertOk()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['j1']), idDeSesion: $e3)->assertOk()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['f1']))->assertUnauthorized()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($m['jb']), idDeSesion: $e1)->assertNotFound()],
        [$jugador, 'get', sesionesPeticion($this, 'GET', graficosJugadorRuta($inexistente), idDeSesion: $e1)->assertNotFound()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: $e1)->assertOk()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], "filtro[jugador_id]={$m['f1']}"), idDeSesion: $e1)->assertOk()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']))->assertUnauthorized()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a']), idDeSesion: $d1)->assertForbidden()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($inexistente), idDeSesion: $e1)->assertNotFound()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], "filtro[jugador_id]=$inexistente"), idDeSesion: $e1)->assertNotFound()],
        [$equipo, 'get', sesionesPeticion($this, 'GET', graficosEquipoRuta($m['a'], 'filtro[jugador_id]=123'), idDeSesion: $e1)->assertUnprocessable()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
