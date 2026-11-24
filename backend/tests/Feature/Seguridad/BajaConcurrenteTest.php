<?php

/*
 * AB-20, AB-26, AB-29 y AB-31 (docs/hitos/f1-h7.md v0.6 §8, T06; DA-127 y DA-128): una escritura que llega mientras
 * otra petición da de baja al jugador. «La otra petición» repite las sentencias de la baja sin confirmar
 * (`concurrenciaBajaDe`); la escritura debe esperar el candado y responder como con el jugador ya en baja, sin escribir.
 * Cada prueba exige además que la escritura haya esperado (`hubo_espera`).
 */

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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
