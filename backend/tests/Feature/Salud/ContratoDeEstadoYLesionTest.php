<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\Ayudantes\NoNulo;
use Tests\TestCase;

/** Las respuestas reales de confirmación, estado oficial y lesión cumplen docs/api/openapi.yaml. */
test('HU-20, HU-21 y HU-30: las respuestas de confirmación, estado y lesión cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = avisosMundo();
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $e2 = sesionesIniciar($this, 'e2@x.com');
    $d1 = sesionesIniciar($this, 'd1@x.com');
    $aviso = avisoInsertar($m['f1'], $m['d1']);
    $avisoDeBaja = avisoInsertar($m['jb'], $m['e1']);
    $avisoConFechaMala = avisoInsertar($m['f1'], $m['e2'], ['fecha_aviso' => '2026-12-25']);
    $confirmacion = '/avisos-lesion/{aviso}/confirmacion';
    $estado = '/jugadores/{jugador}/estado-disponibilidad';
    $alta = '/jugadores/{jugador}/estado-disponibilidad/alta';
    $lesiones = '/jugadores/{jugador}/lesiones';
    $lesion = '/lesiones/{lesion}';
    $inexistente = Identificador::generar()->valor();

    $respuestas = [
        [$estado, 'get', sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $e1)->assertOk()],
        [$estado, 'get', sesionesPeticion($this, 'GET', estadoRuta($m['f1']))->assertUnauthorized()],
        [$estado, 'get', sesionesPeticion($this, 'GET', estadoRuta($m['j1']), idDeSesion: $e2)->assertNotFound()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'lesionado'], $e1)->assertConflict()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $e1)->assertConflict()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), [], $e2)->assertUnprocessable()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo())->assertUnauthorized()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $d1)->assertForbidden()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($inexistente), confirmacionCuerpo(), $e2)->assertNotFound()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($avisoDeBaja), confirmacionCuerpo(), $e1)->assertConflict()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($avisoConFechaMala), confirmacionCuerpo(), $e2)->assertUnprocessable()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $e2)->assertOk()],
        [$confirmacion, 'post', sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $e2)->assertConflict()],
    ];

    $lesionId = NoNulo::exigir(sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $e2)->json('datos.lesion_id'), 'la lesión');
    relojAdelantar('2026-11-15 18:00:01');

    $respuestas = [
        ...$respuestas,
        [$estado, 'get', sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $e2)->assertOk()],
        [$estado, 'get', sesionesPeticion($this, 'GET', estadoRuta($m['f1']), idDeSesion: $d1)->assertOk()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), [], $e2)->assertUnprocessable()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'])->assertUnauthorized()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $d1)->assertForbidden()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $e1)->assertForbidden()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['j1']), ['estado' => 'con_molestia'], $e2)->assertNotFound()],
        [$estado, 'patch', sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $e2)->assertOk()],
        [$lesiones, 'get', sesionesPeticion($this, 'GET', lesionesRuta($m['f1']), idDeSesion: $e2)->assertOk()],
        [$lesiones, 'get', sesionesPeticion($this, 'GET', lesionesRuta($m['f1']).'?pagina=0', idDeSesion: $e2)->assertUnprocessable()],
        [$lesiones, 'get', sesionesPeticion($this, 'GET', lesionesRuta($m['f1']))->assertUnauthorized()],
        [$lesiones, 'get', sesionesPeticion($this, 'GET', lesionesRuta($m['f1']), idDeSesion: $d1)->assertForbidden()],
        [$lesiones, 'get', sesionesPeticion($this, 'GET', lesionesRuta($m['j1']), idDeSesion: $e2)->assertNotFound()],
        [$lesion, 'get', sesionesPeticion($this, 'GET', lesionRuta($lesionId), idDeSesion: $e2)->assertOk()],
        [$lesion, 'get', sesionesPeticion($this, 'GET', lesionRuta($lesionId))->assertUnauthorized()],
        [$lesion, 'get', sesionesPeticion($this, 'GET', lesionRuta($lesionId), idDeSesion: $d1)->assertForbidden()],
        [$lesion, 'get', sesionesPeticion($this, 'GET', lesionRuta($lesionId), idDeSesion: $e1)->assertNotFound()],
        [$lesion, 'patch', sesionesPeticion($this, 'PATCH', lesionRuta($lesionId), ['zona' => 'Rodilla'], $e2)->assertOk()],
        [$lesion, 'patch', sesionesPeticion($this, 'PATCH', lesionRuta($lesionId), [], $e2)->assertUnprocessable()],
        [$lesion, 'patch', sesionesPeticion($this, 'PATCH', lesionRuta($lesionId), ['zona' => 'Rodilla'])->assertUnauthorized()],
        [$lesion, 'patch', sesionesPeticion($this, 'PATCH', lesionRuta($lesionId), ['zona' => 'Rodilla'], $d1)->assertForbidden()],
        [$lesion, 'patch', sesionesPeticion($this, 'PATCH', lesionRuta($lesionId), ['zona' => 'Rodilla'], $e1)->assertNotFound()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), ['fecha_alta' => '2026-01-01'], $e2)->assertUnprocessable()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), [])->assertUnauthorized()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $d1)->assertForbidden()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $e1)->assertForbidden()],
        [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['j1']), [], $e2)->assertNotFound()],
    ];

    relojAdelantar('2026-11-15 18:00:02');
    $respuestas[] = [$alta, 'post', sesionesPeticion($this, 'POST', altaRuta($m['f1']), [], $e2)->assertOk()];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
