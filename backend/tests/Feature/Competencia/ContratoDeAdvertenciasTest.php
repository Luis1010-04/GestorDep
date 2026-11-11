<?php

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

/** La advertencia jugador_lesionado, con su campo, cumple docs/api/openapi.yaml en la asistencia y la convocatoria (DA-15). */
beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-05 15:00:00', new DateTimeZone('UTC'))));
});

test('DA-15: la advertencia con campo de la asistencia y de la convocatoria cumple el contrato', function () {
    /** @var TestCase $this */
    $validador = ValidatorBuilder::fromYamlFile(base_path('../docs/api/openapi.yaml'))->getValidator();
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $entrenamiento = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-01 23:00:00+00', '2026-09-01 23:59:00+00', ['estado' => 'realizado']);
    $partido = mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'torneo_id' => null, 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);

    $asistencia = sesionesPeticion($this, 'POST', "/api/v1/entrenamientos/$entrenamiento/asistencias", ['registros' => [
        ['jugador_id' => $m['j1'], 'estado' => 'presente'], ['jugador_id' => $m['j3'], 'estado' => 'presente'],
    ]], $sesion)->assertCreated()->assertJsonPath('advertencias.0.campo', 'registros.1.jugador_id');
    $convocatoria = sesionesPeticion($this, 'POST', "/api/v1/partidos/$partido/convocatoria", ['jugador_id' => $m['j3']], $sesion)
        ->assertCreated()
        ->assertJsonPath('advertencias.0.campo', 'jugador_id');

    expect($validador->validate($asistencia->baseResponse, '/entrenamientos/{entrenamiento}/asistencias', 'post'))->toBeTrue()
        ->and($validador->validate($convocatoria->baseResponse, '/partidos/{partido}/convocatoria', 'post'))->toBeTrue();
});
