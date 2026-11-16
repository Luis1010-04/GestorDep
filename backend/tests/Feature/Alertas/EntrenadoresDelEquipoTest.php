<?php

use Tests\TestCase;

/**
 * Alertas deportivas y de sanciones con dos entrenadores en el equipo (DA-102, DA-103, DA-107, DA-92, DA-116). A y A2
 * entrenan EA; las filas de `entrenador_equipo` de A2 se crean directo, porque en la Fase 1 no hay ruta para asignar
 * entrenadores adicionales (DA-114).
 */
test('DA-102: con dos entrenadores en el equipo, basta un no_pasa para activar el riesgo de baja deportiva', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a2'], 'no_pasa', '5.00');

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_DEPORTIVA))->toBe(1);
});

test('DA-102: con dos entrenadores, uno que pasa y otro sin evaluación, el resultado es pasa y no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_DEPORTIVA))->toBe([]);
});

test('DA-107: sin ninguna evaluación guardada el resultado es datos_insuficientes', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_DEPORTIVA)))->toEqual(['resultado' => 'datos_insuficientes', 'promedio' => null, 'acumulacion_sanciones' => true]);
});

test('DA-102: la tarjeta que registró solo el segundo entrenador cuenta para la acumulación de sanciones', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a2'], 0, 1);

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_SANCIONES))->toBe(1);
});

test('DA-103: dos entrenadores registran la misma tarjeta en un partido y cuenta una sola vez', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $partido = alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00');
    alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a'], 2, 0);
    alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a2'], 2, 0);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('DA-103: si dos entrenadores registran cantidades distintas en un partido, cuenta la mayor', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $primero = alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00');
    alertasTarjetas($primero, $m['j1'], $m['ea'], $m['a'], 1, 0);
    alertasTarjetas($primero, $m['j1'], $m['ea'], $m['a2'], 2, 0);
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-05 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 1, 0);

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_SANCIONES)))->toEqual(['tarjetas_amarillas' => 3, 'tarjetas_rojas' => 0, 'faltas_en_ventana' => 0]);
});

test('DA-92: de cada entrenador cuenta su última evaluación y no las anteriores', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.00', '2026-03-01 12:00:00+00');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00', '2026-03-09 12:00:00+00');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_DEPORTIVA))->toBe([]);
});

test('DA-116: con varios no_pasa, el promedio de la causa es el menor de ellos', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'no_pasa', '5.50');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a2'], 'no_pasa', '4.00');

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_DEPORTIVA)))->toEqual(['resultado' => 'no_pasa', 'promedio' => '4.00', 'acumulacion_sanciones' => false]);
});

test('DA-116: sin ningún no_pasa, el promedio de la causa es el mayor de los demás', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'pasa', '8.00');
    alertasEvaluacion($m['j1'], $m['ea'], $m['a2'], 'pasa', '9.00');
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_DEPORTIVA)))->toEqual(['resultado' => 'pasa', 'promedio' => '9.00', 'acumulacion_sanciones' => true]);
});

test('DA-116: con un pasa de promedio menor y datos_insuficientes, el promedio es el del que pasa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasEvaluacion($m['j1'], $m['ea'], $m['a'], 'datos_insuficientes', null);
    alertasEvaluacion($m['j1'], $m['ea'], $m['a2'], 'pasa', '8.00');
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    expect(alertasCausa(alertasUnica($m['j1'], ALERTA_DEPORTIVA))['promedio'])->toBe('8.00');
});
