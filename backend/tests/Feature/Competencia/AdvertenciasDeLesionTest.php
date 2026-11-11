<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/** Advertencia jugador_lesionado en la asistencia y la convocatoria (HU-11 c2, HU-12 c3; docs/hitos/f1-h5.md §7.7, DA-15). */
const MENSAJE_DE_JUGADOR_LESIONADO = 'El jugador no está disponible por una lesión.';

beforeEach(function () {
    sesionesPrepararBase();
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-09-05 15:00:00', new DateTimeZone('UTC'))));
});

/**
 * @param  array<string, mixed>  $cuerpo
 * @return TestResponse<Response>
 */
function advertenciaPeticion(TestCase $prueba, string $metodo, string $ruta, array $cuerpo = []): TestResponse
{
    return sesionesPeticion($prueba, $metodo, '/api/v1'.$ruta, $cuerpo, sesionesIniciar($prueba, 'e1@x.com'));
}

/** @param  array<string, string>  $m */
function advertenciaEntrenamiento(array $m): string
{
    return mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-01 23:00:00+00', '2026-09-01 23:59:00+00', ['estado' => 'realizado']);
}

/** @param  array<string, string>  $m */
function advertenciaPartido(array $m): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'torneo_id' => null, 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
}

/** @return array<string, mixed> */
function advertenciaRegistros(string ...$jugadores): array
{
    return ['registros' => array_map(fn (string $jugador) => ['jugador_id' => $jugador, 'estado' => 'presente'], $jugadores)];
}

test('§7.7 fila 1 (HU-11 c2, DA-15, DA-66): con F1 lesionado y J1 apto, una advertencia en registros.1.jugador_id', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1']);

    $respuesta = advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j1'], $m['j3']))
        ->assertCreated();

    expect($respuesta->json('advertencias'))->toBe([
        ['codigo' => 'jugador_lesionado', 'mensaje' => MENSAJE_DE_JUGADOR_LESIONADO, 'campo' => 'registros.1.jugador_id'],
    ])
        ->and($respuesta->json('datos'))->toHaveCount(2);
});

test('DA-66: el mensaje no lleva nombre, apellidos ni matrícula del jugador, ni datos de la lesión', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1']);

    $mensaje = (string) advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j3']))
        ->assertCreated()
        ->json('advertencias.0.mensaje');

    expect($mensaje)->toBe(MENSAJE_DE_JUGADOR_LESIONADO)
        ->and($mensaje)->not->toContain('J3')
        ->and($mensaje)->not->toContain('Tres')
        ->and($mensaje)->not->toContain('M-3')
        ->and($mensaje)->not->toContain('Esguince')
        ->and($mensaje)->not->toContain('Tobillo');
});

test('§7.7 fila 2 (DA-15): con F1 con_molestia, la asistencia no advierte', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1'], 'con_molestia');

    $respuesta = advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j1'], $m['j3']))
        ->assertCreated();

    expect($respuesta->json())->not->toHaveKey('advertencias');
});

test('§7.7 fila 3 (HU-12 c3, DA-15): convocar a F1 lesionado guarda la convocatoria y advierte en jugador_id', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1']);
    $partido = advertenciaPartido($m);

    $respuesta = advertenciaPeticion($this, 'POST', "/partidos/$partido/convocatoria", ['jugador_id' => $m['j3']])->assertCreated();

    expect($respuesta->json('advertencias'))->toBe([['codigo' => 'jugador_lesionado', 'mensaje' => MENSAJE_DE_JUGADOR_LESIONADO, 'campo' => 'jugador_id']])
        ->and($respuesta->json('datos.jugador_id'))->toBe($m['j3'])
        ->and(DB::table('convocatorias')->where('partido_id', $partido)->where('jugador_id', $m['j3'])->count())->toBe(1);
});

test('§7.7 fila 4 (DA-01): con F1 apto implícito, la convocatoria no advierte', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $respuesta = advertenciaPeticion($this, 'POST', '/partidos/'.advertenciaPartido($m).'/convocatoria', ['jugador_id' => $m['j3']])->assertCreated();

    expect($respuesta->json())->not->toHaveKey('advertencias');
});

test('§7.7 fila 5: con F1 dado de alta tras estar lesionado, la convocatoria no advierte', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    // El apto vigente se inserta primero y el lesionado cerrado después: solo el filtro de vigencia evita la advertencia.
    mundoInsertar('estados_disponibilidad', [
        'jugador_id' => $m['j3'], 'estado' => 'apto', 'confirmado_por' => $m['e1'], 'vigente_desde' => '2026-11-12 10:00:00+00',
    ]);
    $lesion = lesionInsertar($m['j3'], $m['e1'], ['fecha_alta' => '2026-11-12']);
    mundoInsertar('estados_disponibilidad', [
        'jugador_id' => $m['j3'], 'estado' => 'lesionado', 'lesion_id' => $lesion, 'fecha_estimada_regreso' => '2026-11-30', 'confirmado_por' => $m['e1'],
        'vigente_desde' => '2026-11-10 10:00:00+00', 'vigente_hasta' => '2026-11-12 10:00:00+00',
    ]);

    $respuesta = advertenciaPeticion($this, 'POST', '/partidos/'.advertenciaPartido($m).'/convocatoria', ['jugador_id' => $m['j3']])->assertCreated();

    expect($respuesta->json())->not->toHaveKey('advertencias');
});

test('§7.7 fila 6 (DA-15): con F1 y J1 lesionados, dos advertencias, en registros.0 y registros.1', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j1'], $m['e1']);
    estadoInsertar($m['j3'], $m['e1']);

    $respuesta = advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j1'], $m['j3'], $m['j2']))
        ->assertCreated();

    expect(array_column($respuesta->json('advertencias'), 'campo'))->toBe(['registros.0.jugador_id', 'registros.1.jugador_id'])
        ->and(array_unique(array_column($respuesta->json('advertencias'), 'codigo')))->toBe(['jugador_lesionado']);
});

test('§7.7 fila 7 (DA-15): un entrenamiento que se empalma responde empalme_de_lugar igual que en H3, sin campo', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 23:00:00+00', '2026-09-11 00:00:00+00');

    $respuesta = advertenciaPeticion($this, 'POST', "/equipos/{$m['a']}/entrenamientos", [
        'inicia_en' => '2026-09-10T17:30:00-06:00', 'termina_en' => '2026-09-10T18:30:00-06:00', 'lugar_id' => $m['l1'], 'responsable_id' => $m['e1'],
    ])->assertCreated();

    expect($respuesta->json('advertencias'))->toBe([['codigo' => 'empalme_de_lugar', 'mensaje' => 'Otro entrenamiento usa el mismo lugar en ese horario.']]);
});

test('DA-67: corregir la asistencia de un jugador lesionado no devuelve la advertencia', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j3'], $m['e1']);
    $asistencia = mundoInsertar('asistencias_entrenamiento', ['entrenamiento_id' => advertenciaEntrenamiento($m), 'jugador_id' => $m['j3'], 'estado' => 'presente']);

    $respuesta = advertenciaPeticion($this, 'PATCH', "/asistencias-entrenamiento/$asistencia", ['estado' => 'falta'])->assertOk();

    expect($respuesta->json())->not->toHaveKey('advertencias')
        ->and($respuesta->json('datos.estado'))->toBe('falta');
});

test('T07: sin jugadores lesionados, la asistencia y la convocatoria responden igual que en H3, sin advertencias', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();

    $asistencia = advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j1'], $m['j2']))->assertCreated();
    $convocatoria = advertenciaPeticion($this, 'POST', '/partidos/'.advertenciaPartido($m).'/convocatoria', ['jugador_id' => $m['j1']])->assertCreated();

    expect(array_keys($asistencia->json()))->toBe(['datos'])
        ->and(array_keys($convocatoria->json()))->toBe(['datos']);
});

test('T07: una petición rechazada no genera advertencias', function () {
    /** @var TestCase $this */
    $m = mundoDeCompetencia();
    estadoInsertar($m['j4'], $m['e2']);

    $respuesta = advertenciaPeticion($this, 'POST', '/entrenamientos/'.advertenciaEntrenamiento($m).'/asistencias', advertenciaRegistros($m['j1'], $m['j4']))
        ->assertUnprocessable();

    expect($respuesta->json())->not->toHaveKey('advertencias');
});
