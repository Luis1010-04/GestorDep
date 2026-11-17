<?php

use Illuminate\Support\Facades\DB;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Identificador;
use Tests\TestCase;

/** Las respuestas reales de la vista consolidada de la convocatoria cumplen docs/api/openapi.yaml (HU-12 c2, DA-96). */

/** Un partido más del equipo indicado, con la temporada, el rival y el lugar del mundo. */
function contratoConvPartido(string $equipo, string $lugar): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $equipo, 'temporada_id' => (string) DB::table('temporadas')->value('id'), 'rival_id' => (string) DB::table('rivales')->value('id'),
        'lugar_id' => $lugar, 'inicia_en' => '2026-10-20 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
}

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
        sesionesPeticion($this, 'GET', $ruta($m['pj1'], 'por_pagina=500'), idDeSesion: $e1)->assertUnprocessable(),
    ];

    foreach ($respuestas as $respuesta) {
        expect($validador->validate($respuesta->baseResponse, $plantilla, 'get'))->toBeTrue("get $plantilla {$respuesta->getStatusCode()}");
    }
});

test('RNF-01 (DA-96): el 404 de un partido ajeno, inexistente, con id que no es v7 o eliminado cumple el esquema de error de openapi.yaml', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = convocarMundo();
    $deB = contratoConvPartido($m['b'], $m['lugar']);
    $eliminado = contratoConvPartido($m['a'], $m['lugar']);
    DB::connection('propietario')->table('partidos')->where('id', $eliminado)->update(['eliminado_en' => '2026-10-01 00:00:00+00']);
    $e1 = sesionesIniciar($this, 'e1@x.com');
    $plantilla = '/partidos/{partido}/convocatoria/vista';

    $casos = [
        'ajeno' => $deB,
        'inexistente' => Identificador::generar()->valor(),
        'no v7' => '00000000-0000-0000-0000-000000000000',
        'eliminado' => $eliminado,
    ];

    foreach ($casos as $caso => $partido) {
        $respuesta = sesionesPeticion($this, 'GET', "/api/v1/partidos/$partido/convocatoria/vista", idDeSesion: $e1)->assertNotFound();

        expect($validador->validate($respuesta->baseResponse, $plantilla, 'get'))->toBeTrue("404 $caso")
            ->and(array_keys($respuesta->json()))->toBe(['codigo', 'mensaje', 'errores'], $caso);
    }
});
