<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de partidos cumplen docs/api/openapi.yaml. */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-12: las respuestas de partidos cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = mundoDeCompetencia();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $coleccion = "/api/v1/equipos/{$m['a']}/partidos";
    $cuerpo = ['inicia_en' => '2026-09-12T17:00:00-06:00', 'lugar_id' => $m['l1'], 'rival_id' => $m['r1'], 'torneo_id' => $m['to1'], 'tipo' => 'oficial', 'condicion' => 'local'];

    $creado = sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $sesion)->assertCreated();
    $amistoso = sesionesPeticion($this, 'POST', $coleccion, [...$cuerpo, 'tipo' => 'amistoso', 'torneo_id' => null], $sesion)->assertCreated();
    $detalle = '/api/v1/partidos/'.$creado->json('datos.id');
    $respuestas = [
        ['/equipos/{equipo}/partidos', 'post', $creado],
        ['/equipos/{equipo}/partidos', 'post', $amistoso],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, [...$cuerpo, 'inicia_en' => '2027-08-01T00:00:00Z'], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, [...$cuerpo, 'torneo_id' => null], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, [...$cuerpo, 'rival_id' => Identificador::generar()->valor()], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, [], $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $ajena)->assertNotFound()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo)->assertUnauthorized()],
        ['/equipos/{equipo}/partidos', 'post', sesionesPeticion($this, 'POST', $coleccion, $cuerpo, $docente)->assertForbidden()],
        ['/equipos/{equipo}/partidos', 'get', sesionesPeticion($this, 'GET', $coleccion."?filtro[temporada_id]={$m['t1']}&filtro[estado]=programado", idDeSesion: $sesion)->assertOk()],
        ['/equipos/{equipo}/partidos', 'get', sesionesPeticion($this, 'GET', $coleccion.'?filtro[estado]=x', idDeSesion: $sesion)->assertUnprocessable()],
        ['/equipos/{equipo}/partidos', 'get', sesionesPeticion($this, 'GET', $coleccion, idDeSesion: $ajena)->assertNotFound()],
        ['/equipos/{equipo}/partidos', 'get', sesionesPeticion($this, 'GET', $coleccion)->assertUnauthorized()],
        ['/equipos/{equipo}/partidos', 'get', sesionesPeticion($this, 'GET', $coleccion, idDeSesion: $docente)->assertForbidden()],
        ['/partidos/{partido}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $sesion)->assertOk()],
        ['/partidos/{partido}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $ajena)->assertNotFound()],
        ['/partidos/{partido}', 'get', sesionesPeticion($this, 'GET', $detalle)->assertUnauthorized()],
        ['/partidos/{partido}', 'get', sesionesPeticion($this, 'GET', $detalle, idDeSesion: $docente)->assertForbidden()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['condicion' => 'visitante'], $sesion)->assertOk()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['torneo_id' => null], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, [], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $sesion)->assertOk()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $sesion)->assertStatus(409)],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['condicion' => 'local'], $sesion)->assertStatus(409)],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $ajena)->assertNotFound()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'])->assertUnauthorized()],
        ['/partidos/{partido}', 'patch', sesionesPeticion($this, 'PATCH', $detalle, ['estado' => 'cancelado'], $docente)->assertForbidden()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
