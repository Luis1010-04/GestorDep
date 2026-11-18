<?php

use Tests\TestCase;

/** Riesgo de baja deportiva (RN-10, HU-22): tabla de docs/hitos/f1-h6.md §7.4, con evaluaciones y sanciones reales. */

/** @param  array<string, string>  $m */
function riesgoConSanciones(array $m): void
{
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
}

test('RN-10 (§7.4 fila 1): no_pasa sin sanciones acumuladas, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.00');

    alertasRecalcular();

    $alerta = alertasUnica($m['j1'], ALERTA_DEPORTIVA);
    expect($alerta->equipo_id)->toBe($m['ea'])
        ->and($alerta->cerrada_en)->toBeNull();
});

test('RN-10 (§7.4 fila 2): pasa con sanciones acumuladas, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00');
    riesgoConSanciones($m);

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_DEPORTIVA))->toBe(1);
});

test('RN-10 (§7.4 fila 3): pasa sin sanciones acumuladas, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_DEPORTIVA))->toBe([]);
});

test('RN-10 (§7.4 fila 4): datos_insuficientes sin sanciones acumuladas, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'datos_insuficientes', null);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_DEPORTIVA))->toBe([]);
});

test('RN-10 (§7.4 fila 5): datos_insuficientes con sanciones acumuladas, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'datos_insuficientes', null);
    riesgoConSanciones($m);

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_DEPORTIVA))->toBe(1);
});
