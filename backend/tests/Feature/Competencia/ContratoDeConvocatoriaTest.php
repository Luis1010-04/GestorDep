<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de las rutas de convocatoria cumplen docs/api/openapi.yaml. */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-12: las respuestas de convocatoria cumplen el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = mundoDeCompetencia();
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $ajena = sesionesIniciar($this, 'e2@x.com');
    $docente = sesionesIniciar($this, 'd@x.com');
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
    $lista = "/api/v1/partidos/$partido/convocatoria";

    $creada = sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j1']], $sesion)->assertCreated();
    $quitar = '/api/v1/convocatorias/'.$creada->json('datos.id');
    $respuestas = [
        ['/partidos/{partido}/convocatoria', 'post', $creada],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j1']], $sesion)->assertStatus(409)],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j5']], $sesion)->assertNotFound()],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, [], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j1']], $ajena)->assertNotFound()],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j1']])->assertUnauthorized()],
        ['/partidos/{partido}/convocatoria', 'post', sesionesPeticion($this, 'POST', $lista, ['jugador_id' => $m['j1']], $docente)->assertForbidden()],
        ['/partidos/{partido}/convocatoria', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $sesion)->assertOk()],
        ['/partidos/{partido}/convocatoria', 'get', sesionesPeticion($this, 'GET', $lista.'?pagina=0', idDeSesion: $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/convocatoria', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $ajena)->assertNotFound()],
        ['/partidos/{partido}/convocatoria', 'get', sesionesPeticion($this, 'GET', $lista)->assertUnauthorized()],
        ['/partidos/{partido}/convocatoria', 'get', sesionesPeticion($this, 'GET', $lista, idDeSesion: $docente)->assertForbidden()],
        ['/convocatorias/{convocatoria}', 'delete', sesionesPeticion($this, 'DELETE', $quitar, idDeSesion: $ajena)->assertNotFound()],
        ['/convocatorias/{convocatoria}', 'delete', sesionesPeticion($this, 'DELETE', $quitar)->assertUnauthorized()],
        ['/convocatorias/{convocatoria}', 'delete', sesionesPeticion($this, 'DELETE', $quitar, idDeSesion: $docente)->assertForbidden()],
        ['/convocatorias/{convocatoria}', 'delete', sesionesPeticion($this, 'DELETE', $quitar, idDeSesion: $sesion)->assertNoContent()],
        ['/convocatorias/{convocatoria}', 'delete', sesionesPeticion($this, 'DELETE', '/api/v1/convocatorias/'.Identificador::generar()->valor(), idDeSesion: $sesion)->assertNotFound()],
    ];

    foreach ($respuestas as [$plantilla, $metodo, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, $metodo))->toBeTrue("$metodo $plantilla {$respuesta->getStatusCode()}");
    }
});
