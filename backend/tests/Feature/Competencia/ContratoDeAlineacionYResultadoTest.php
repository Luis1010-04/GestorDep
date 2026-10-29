<?php

use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Tests\TestCase;

/** Las respuestas reales de la alineación y el resultado cumplen docs/api/openapi.yaml. */
beforeEach(function () {
    sesionesPrepararBase();
});

test('HU-13: las respuestas de alineación y resultado cumplen el contrato', function () {
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
    mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $m['j1']]);
    $defensa = (string) DB::table('posiciones')->where('codigo', 'defensa')->value('id');
    $alineacion = "/api/v1/partidos/$partido/alineacion";
    $resultado = "/api/v1/partidos/$partido/resultado";
    $cuerpo = ['alineacion' => [['jugador_id' => $m['j1'], 'posicion_id' => $defensa, 'rol_alineacion' => 'titular']]];

    $respuestas = [
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, $cuerpo, $sesion)->assertOk()],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, ['alineacion' => [['jugador_id' => $m['j2'], 'posicion_id' => $defensa, 'rol_alineacion' => 'titular']]], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, [], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, $cuerpo, $ajena)->assertNotFound()],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, $cuerpo)->assertUnauthorized()],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, $cuerpo, $docente)->assertForbidden()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 100, 'goles_contra' => 0], $sesion)->assertUnprocessable()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 3, 'goles_contra' => 1], $ajena)->assertNotFound()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 3, 'goles_contra' => 1])->assertUnauthorized()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 3, 'goles_contra' => 1], $docente)->assertForbidden()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 3, 'goles_contra' => 1], $sesion)->assertOk()],
        ['/partidos/{partido}/resultado', sesionesPeticion($this, 'PUT', $resultado, ['goles_favor' => 3, 'goles_contra' => 1], $sesion)->assertStatus(409)],
        ['/partidos/{partido}/alineacion', sesionesPeticion($this, 'PUT', $alineacion, $cuerpo, $sesion)->assertStatus(409)],
    ];

    foreach ($respuestas as [$plantilla, $respuesta]) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, 'put'))->toBeTrue("put $plantilla {$respuesta->getStatusCode()}");
    }
});
