<?php

/*
 * AB-20, AB-26, AB-29 y AB-31 (docs/hitos/f1-h7.md v0.6 §8, T06; DA-127 y DA-128): una escritura que llega mientras
 * otra petición da de baja al jugador. «La otra petición» repite las sentencias de la baja sin confirmar
 * (`concurrenciaBajaDe`); la escritura debe esperar el candado y responder como con el jugador ya en baja, sin escribir.
 * Cada prueba exige además que la escritura haya esperado (`hubo_espera`).
 */

use Illuminate\Support\Facades\DB;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojFijo;
use Tests\TestCase;

function bajaConcurrenteReloj(string $momentoUtc): void
{
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable($momentoUtc, new DateTimeZone('UTC'))));
}

/**
 * Un partido programado de A en T1, como el de las pruebas de convocatoria y alineación de H3.
 *
 * @param  array<string, string>  $m
 */
function bajaConcurrentePartido(array $m): string
{
    return mundoInsertar('partidos', [
        'equipo_id' => $m['a'], 'temporada_id' => $m['t1'], 'rival_id' => $m['r1'], 'lugar_id' => $m['l1'],
        'inicia_en' => '2026-09-12 23:00:00+00', 'tipo' => 'amistoso', 'condicion' => 'local', 'estado' => 'programado',
    ]);
}

test('AB-20: una asignación concurrente con la baja responde 409 jugador_en_baja y no crea la membresía', function () {
    /** @var TestCase $this */
    $m = mundoDeJugadores();
    $sesion = sesionesIniciar($this, 'b@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/equipos/{$m['eb']}/jugadores", ['matricula' => 'M-1', 'posicion' => 'medio'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('jugador_equipo')->where('jugador_id', $m['j1'])->where('equipo_id', $m['eb'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-26: una inscripción concurrente con la baja responde 409 jugador_en_baja y no crea la inscripción', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j4']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/materias/{$m['m1']}/inscripciones", ['matricula' => 'M-4'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('inscripciones')->where('jugador_id', $m['j4'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-26: una calificación concurrente con la baja responde 409 y no se guarda', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/calificaciones", ['tipo' => 'final', 'valor' => '8.00'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('calificaciones')->where('inscripcion_id', $m['i1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-26: la corrección de una calificación concurrente con la baja responde 409 y no la cambia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $previa = mundoCalificar($m['i1'], 'parcial', '7.00', 1);
    $sesion = sesionesIniciar($this, 'd1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'PATCH', "/api/v1/calificaciones/$previa", ['valor' => '9.00'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('calificaciones')->where('id', $previa)->value('valor'))->toBe('7.00')
        ->and($espero)->toBeTrue();
});

test('AB-26: una asistencia a clase concurrente con la baja responde 409 y no se guarda', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $sesion = sesionesIniciar($this, 'd1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/inscripciones/{$m['i1']}/asistencias", ['estado' => 'presente'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('asistencias_clase')->where('inscripcion_id', $m['i1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-26: la corrección de una asistencia a clase concurrente con la baja responde 409 y no la cambia', function () {
    /** @var TestCase $this */
    $m = mundoDeDocentes();
    $previa = mundoAsistir($m['i1'], '2026-03-02', 'falta');
    $sesion = sesionesIniciar($this, 'd1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'PATCH', "/api/v1/asistencias-clase/$previa", ['estado' => 'falta_justificada'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('asistencias_clase')->where('id', $previa)->value('estado'))->toBe('falta')
        ->and($espero)->toBeTrue();
});

test('AB-29: un aviso concurrente con la baja responde 409 y no se guarda', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'POST', avisosRuta($m['f1']), ['nota' => 'Molestia en el tobillo', 'fecha' => '2026-11-14'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('avisos_lesion')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-29: la confirmación de un aviso concurrente con la baja responde 409 y no cambia el estado', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $aviso = avisoInsertar($m['f1'], $m['e1']);
    $sesion = sesionesIniciar($this, 'e2@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'POST', avisoConfirmacionRuta($aviso), confirmacionCuerpo(), $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('avisos_lesion')->where('id', $aviso)->value('estado'))->toBe('pendiente')
        ->and(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and(DB::table('lesiones')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-29: un cambio de estado concurrente con la baja responde 409 y no cambia el estado', function () {
    /** @var TestCase $this */
    $m = avisosMundo();
    $estado = estadoInsertar($m['f1'], $m['e2'])['estado'];
    $sesion = sesionesIniciar($this, 'e2@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'PATCH', estadoRuta($m['f1']), ['estado' => 'con_molestia'], $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('estados_disponibilidad')->where('jugador_id', $m['f1'])->count())->toBe(1)
        ->and(DB::table('estados_disponibilidad')->where('id', $estado)->value('vigente_hasta'))->toBeNull()
        ->and($espero)->toBeTrue();
});

test('AB-29: una medición concurrente con la baja responde 409 y no se guarda', function () {
    /** @var TestCase $this */
    $m = medicionesMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'POST', medicionesRuta($m['f1']), medicionCuerpo($m['a']), $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('mediciones_corporales')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-29: una prueba física concurrente con la baja responde 409 y no se guarda', function () {
    /** @var TestCase $this */
    $m = pruebasMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'POST', pruebasRuta($m['f1']), pruebaCuerpo($m['a'], $m['velocidad']), $sesion),
    );

    $respuesta->assertStatus(409)->assertJsonPath('codigo', 'jugador_en_baja');
    expect(DB::table('pruebas_fisicas')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-31: una convocatoria concurrente con la baja responde 404 y no se guarda', function () {
    /** @var TestCase $this */
    sesionesPrepararBase();
    bajaConcurrenteReloj('2026-09-05 15:00:00');
    $m = mundoDeCompetencia();
    $partido = bajaConcurrentePartido($m);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/partidos/$partido/convocatoria", ['jugador_id' => $m['j1']], $sesion),
    );

    $respuesta->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    expect(DB::table('convocatorias')->where('jugador_id', $m['j1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-31: una alineación concurrente con la baja responde 404 y no cambia', function () {
    /** @var TestCase $this */
    sesionesPrepararBase();
    bajaConcurrenteReloj('2026-09-13 01:00:00');
    $m = mundoDeCompetencia();
    $partido = bajaConcurrentePartido($m);
    mundoInsertar('convocatorias', ['partido_id' => $partido, 'jugador_id' => $m['j1']]);
    $defensa = (string) DB::table('posiciones')->where('codigo', 'defensa')->value('id');
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'PUT', "/api/v1/partidos/$partido/alineacion", ['alineacion' => [
            ['jugador_id' => $m['j1'], 'posicion_id' => $defensa, 'rol_alineacion' => 'titular'],
        ]], $sesion),
    );

    $respuesta->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    expect(DB::table('convocatorias')->where('partido_id', $partido)->whereNotNull('rol_alineacion')->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-31: una asistencia de entrenamiento concurrente con la baja responde 422 jugador_no_pertenece_al_equipo (JugadorNoPerteneceAlEquipo) y no se guarda', function () {
    /** @var TestCase $this */
    sesionesPrepararBase();
    bajaConcurrenteReloj('2026-09-20 15:00:00');
    $m = mundoDeCompetencia();
    $entrenamiento = mundoEntrenamiento($m['a'], $m['l1'], $m['e1'], '2026-09-10 23:00:00+00', '2026-09-10 23:59:00+00', ['estado' => 'realizado']);
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['j1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/entrenamientos/$entrenamiento/asistencias", ['registros' => [
            ['jugador_id' => $m['j1'], 'estado' => 'presente'],
        ]], $sesion),
    );

    $respuesta->assertUnprocessable()
        ->assertJsonPath('codigo', 'jugador_no_pertenece_al_equipo')
        ->assertJsonPath('errores.0.campo', 'registros.0.jugador_id')
        ->assertJsonPath('errores.0.codigo', 'jugador_no_pertenece_al_equipo');
    expect(DB::table('asistencias_entrenamiento')->where('entrenamiento_id', $entrenamiento)->count())->toBe(0)
        ->and($espero)->toBeTrue();
});

test('AB-31: un rendimiento concurrente con la baja responde 404 y no se guarda', function () {
    /** @var TestCase $this */
    sesionesPrepararBase();
    bajaConcurrenteReloj('2026-09-20 15:00:00');
    $m = capturaMundo();
    $sesion = sesionesIniciar($this, 'e1@x.com');

    ['resultado' => $respuesta, 'hubo_espera' => $espero] = concurrenciaMientrasOtraTransaccion(
        concurrenciaBajaDe($m['f1']),
        fn () => sesionesPeticion($this, 'POST', "/api/v1/partidos/{$m['pj1']}/rendimientos", capturaCuerpo($m['f1'], 'delantero'), $sesion),
    );

    $respuesta->assertNotFound()->assertJsonPath('codigo', 'recurso_no_encontrado');
    expect(DB::table('rendimientos_partido')->where('jugador_id', $m['f1'])->count())->toBe(0)
        ->and($espero)->toBeTrue();
});
