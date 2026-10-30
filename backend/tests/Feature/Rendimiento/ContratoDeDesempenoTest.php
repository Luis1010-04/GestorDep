<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de la ruta de desempeño cumplen docs/api/openapi.yaml (HU-16). */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-16: las respuestas del desempeño cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = umbralMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ruta = "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/desempeno";

    $sinDatos = sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertOk();
    foreach ([7, 7, 8, 6, 5] as $indice => $calificacion) {
        $partido = desempenoPartidoJugado($m['a'], '2026-08-0'.($indice + 1).' 23:00:00+00', [$m['f1']]);
        sesionesPeticion($this, 'POST', "/api/v1/partidos/{$partido}/rendimientos", capturaCuerpo($m['f1'], 'delantero', ['calificacion_general' => $calificacion]), $sesion)->assertCreated();
    }
    $respuestas = [
        $sinDatos,
        sesionesPeticion($this, 'GET', $ruta, idDeSesion: $sesion)->assertOk(),
        sesionesPeticion($this, 'GET', $ruta, idDeSesion: sesionesIniciar($this, 'e2@x.com'))->assertNotFound(),
        sesionesPeticion($this, 'GET', $ruta)->assertUnauthorized(),
        sesionesPeticion($this, 'GET', $ruta, idDeSesion: sesionesIniciar($this, 'd@x.com'))->assertForbidden(),
    ];

    foreach ($respuestas as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, '/equipos/{equipo}/jugadores/{jugador}/desempeno', 'get'))->toBeTrue((string) $respuesta->getStatusCode());
    }
});
