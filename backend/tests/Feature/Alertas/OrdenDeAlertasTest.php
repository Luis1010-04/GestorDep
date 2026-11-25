<?php

/*
 * Desempate del orden de las alertas (docs/hitos/f1-h7.md v0.6 §8, T07; §3.1): `ConsultaDeAlertasSql::deLosJugadores`
 * ordena por `activada_en` y después por `id`. Alimenta `GET /jugadores/{jugador}/alertas` y el campo `alertas` de
 * `GET /estatus-de-jugadores`. Los identificadores se fijan en la prueba, en un orden distinto al de los tipos y al de
 * inserción, para no depender de la generación del UUID v7. Mundo: `estatusMundo()` (E1 entrena A, donde está F1).
 */

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

test('Orden: con el mismo activada_en, las alertas salen por id', function () {
    /** @var TestCase $this */
    $m = estatusMundo();
    $alertas = [
        '01936b8e-0000-7000-8000-000000000003' => [ALERTA_ACADEMICA, null],
        '01936b8e-0000-7000-8000-000000000001' => [ALERTA_DEPORTIVA, $m['a']],
        '01936b8e-0000-7000-8000-000000000002' => [ALERTA_SANCIONES, $m['a']],
    ];
    foreach ($alertas as $id => [$tipo, $equipo]) {
        DB::connection('propietario')->table('alertas')->insert([
            'id' => $id, 'tipo' => $tipo, 'jugador_id' => $m['f1'], 'equipo_id' => $equipo, 'causa' => json_encode(['dato' => 'x']),
            'activada_en' => '2026-11-15 08:00:00+00',
        ]);
    }
    $sesion = sesionesIniciar($this, 'e1@x.com');
    $esperado = [ALERTA_DEPORTIVA, ALERTA_SANCIONES, ALERTA_ACADEMICA];

    /** @var list<array{jugador: array{id: string}, alertas: list<array{tipo: string}>}> $filas */
    $filas = sesionesPeticion($this, 'GET', estatusRuta(), idDeSesion: $sesion)->assertOk()->json('datos');
    $filaDeF1 = array_values(array_filter($filas, fn (array $fila): bool => $fila['jugador']['id'] === $m['f1']))[0];

    /** @var list<array{tipo: string}> $delJugador */
    $delJugador = sesionesPeticion($this, 'GET', alertasRuta($m['f1']), idDeSesion: $sesion)->assertOk()->json('datos');

    expect(array_column($delJugador, 'tipo'))->toBe($esperado)
        ->and(array_column($filaDeF1['alertas'], 'tipo'))->toBe($esperado);
});
