<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Acumulación de sanciones deportivas (RN-09, HU-22): tabla de docs/hitos/f1-h6.md §7.3. Hoy de negocio: 2026-03-10 (la
 * ventana de faltas va del 2026-02-09 al 2026-03-10). Las horas de los entrenamientos son del mediodía en México (UTC-6).
 */
test('RN-09 (§7.3 fila 1): 1 tarjeta roja en la temporada, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    $alerta = alertasUnica($m['j1'], ALERTA_SANCIONES);
    expect($alerta->cerrada_en)->toBeNull()
        ->and($alerta->equipo_id)->toBe($m['ea']);
});

test('RN-09 (§7.3 fila 2): 2 tarjetas amarillas en la temporada, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 2, 0);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('RN-09 (§7.3 fila 3): 3 tarjetas amarillas en la temporada, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 2, 0);
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-05 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 1, 0);

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_SANCIONES))->toBe(1);
});

test('RN-09 (§7.3 fila 4): 3 faltas en 30 días, se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    foreach (['2026-02-15', '2026-03-01', '2026-03-10'] as $dia) {
        alertasFalta($m, $m['j1'], "$dia 18:00:00+00");
    }

    alertasRecalcular();

    expect(alertasAbiertas($m['j1'], ALERTA_SANCIONES))->toBe(1);
});

test('RN-09 (§7.3 fila 5): 2 faltas y 1 falta justificada en 30 días, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasFalta($m, $m['j1'], '2026-02-15 18:00:00+00');
    alertasFalta($m, $m['j1'], '2026-03-01 18:00:00+00');
    alertasFalta($m, $m['j1'], '2026-03-10 18:00:00+00', 'falta_justificada');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('RN-09 (§7.3 fila 6): 3 faltas repartidas en 31 días, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    // La spec no da fechas; con hoy 2026-03-10 la ventana empieza el 2026-02-09, así que la falta del 02-08 abarca el día 31.
    foreach (['2026-02-08', '2026-02-24', '2026-03-10'] as $dia) {
        alertasFalta($m, $m['j1'], "$dia 18:00:00+00");
    }

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('RN-09 (§7.3 fila 7): 1 tarjeta roja de la temporada anterior, no se activa', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2025'], '2025-11-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('RN-09 (§7.3 fila 8): alerta abierta por 3 faltas; en el recálculo diario la más antigua ya salió de la ventana, se cierra', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    foreach (['2026-02-10', '2026-02-20', '2026-03-05'] as $dia) {
        alertasFalta($m, $m['j1'], "$dia 18:00:00+00");
    }
    alertasRecalcular();
    $abierta = alertasUnica($m['j1'], ALERTA_SANCIONES);
    expect($abierta->cerrada_en)->toBeNull();

    alertasFijarReloj('2026-03-12 12:00:00');
    alertasRecalcular();

    $cerrada = alertasUnica($m['j1'], ALERTA_SANCIONES);
    expect($cerrada->id)->toBe($abierta->id)
        ->and($cerrada->cerrada_en)->not->toBeNull()
        ->and($cerrada->activada_en)->toBe($abierta->activada_en);
});

test('RN-09 (§7.3 fila 9, ADR-07): alerta abierta por 1 roja; el recálculo ocurre después del fin de la temporada, se cierra y conserva activada_en', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);
    alertasRecalcular();
    $abierta = alertasUnica($m['j1'], ALERTA_SANCIONES);

    alertasFijarReloj('2027-01-05 12:00:00');
    alertasRecalcular();

    $cerrada = alertasUnica($m['j1'], ALERTA_SANCIONES);
    expect($cerrada->id)->toBe($abierta->id)
        ->and($cerrada->cerrada_en)->not->toBeNull()
        ->and($cerrada->activada_en)->toBe($abierta->activada_en);
});

test('DA-117: las tarjetas de un partido eliminado no cuentan', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    $partido = alertasPartido($m, $m['t2026'], '2026-03-01 18:00:00+00');
    alertasTarjetas($partido, $m['j1'], $m['ea'], $m['a'], 0, 1);
    DB::connection('propietario')->table('partidos')->where('id', $partido)->update(['eliminado_en' => '2026-03-02 00:00:00+00']);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});

test('DA-117: las tarjetas de un partido cuya fecha no cae en ninguna temporada no cuentan', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasTarjetas(alertasPartido($m, $m['t2025'], '2024-05-01 18:00:00+00'), $m['j1'], $m['ea'], $m['a'], 0, 1);

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_SANCIONES))->toBe([]);
});
