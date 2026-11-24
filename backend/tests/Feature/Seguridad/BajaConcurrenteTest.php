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
