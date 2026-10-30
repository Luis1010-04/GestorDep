<?php

use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de las rutas de plantillas y umbral cumplen docs/api/openapi.yaml (HU-15). */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-15: las respuestas de plantillas y umbral cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = umbralMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $plantilla = ['nombre' => 'Exigente', 'posicion_id' => umbralPosicion('delantero'), 'calificacion_minima' => '7.50', 'ventana_partidos' => 4];
    $umbral = "/api/v1/equipos/{$m['a']}/jugadores/{$m['f1']}/umbral";

    $creada = sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', $plantilla, $sesion)->assertCreated();
    $respuestas = [
        ['/plantillas-evaluacion', 'post', $creada],
        ['/plantillas-evaluacion', 'post', sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', $plantilla, $sesion)->assertStatus(409)],
        ['/plantillas-evaluacion', 'post', sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', [...$plantilla, 'ventana_partidos' => 0], $sesion)->assertUnprocessable()],
        ['/plantillas-evaluacion', 'post', sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', [], $sesion)->assertUnprocessable()],
        ['/plantillas-evaluacion', 'post', sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', $plantilla)->assertUnauthorized()],
        ['/plantillas-evaluacion', 'post', sesionesPeticion($this, 'POST', '/api/v1/plantillas-evaluacion', $plantilla, $docente)->assertForbidden()],
        ['/plantillas-evaluacion', 'get', sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion', idDeSesion: $sesion)->assertOk()],
        ['/plantillas-evaluacion', 'get', sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion?filtro[posicion_id]='.umbralPosicion('delantero'), idDeSesion: $sesion)->assertOk()],
        ['/plantillas-evaluacion', 'get', sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion?filtro[posicion_id]=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/plantillas-evaluacion', 'get', sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion')->assertUnauthorized()],
        ['/plantillas-evaluacion', 'get', sesionesPeticion($this, 'GET', '/api/v1/plantillas-evaluacion', idDeSesion: $docente)->assertForbidden()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $umbral, idDeSesion: $sesion)->assertOk()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, ['calificacion_minima' => '7.00', 'ventana_partidos' => 3], $sesion)->assertOk()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $umbral, idDeSesion: $sesion)->assertOk()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, ['plantilla_id' => $creada->json('datos.id')], $sesion)->assertOk()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, ['plantilla_id' => umbralPlantillaDeFabrica('portero')], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, ['calificacion_minima' => '7.00'], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $umbral, idDeSesion: $ajena)->assertNotFound()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, [], $ajena)->assertNotFound()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $umbral)->assertUnauthorized()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral)->assertUnauthorized()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $umbral, idDeSesion: $docente)->assertForbidden()],
        ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $umbral, [], $docente)->assertForbidden()],
    ];

    // 409 sin_umbral_aplicable (DA-33, v1.0.6): un portero sin umbral propio cuando no hay plantilla de fábrica de portero.
    $portero = mundoJugador('M-SINP', 'Sin plantilla', [$m['a'] => 'portero'], nombres: 'SP');
    DB::connection('propietario')->table('plantillas_evaluacion')->where('id', umbralPlantillaDeFabrica('portero'))->update(['eliminado_en' => '2026-09-30 10:00:00+00']);
    $sinPlantilla = "/api/v1/equipos/{$m['a']}/jugadores/{$portero}/umbral";
    $respuestas[] = ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'get', sesionesPeticion($this, 'GET', $sinPlantilla, idDeSesion: $sesion)->assertStatus(409)->assertJsonPath('codigo', 'sin_umbral_aplicable')];
    $respuestas[] = ['/equipos/{equipo}/jugadores/{jugador}/umbral', 'put', sesionesPeticion($this, 'PUT', $sinPlantilla, [], $sesion)->assertStatus(409)->assertJsonPath('codigo', 'sin_umbral_aplicable')];

    foreach ($respuestas as [$ruta, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $ruta, $metodo))->toBeTrue("$metodo $ruta {$respuesta->getStatusCode()}");
    }
});
