<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Src\Alertas\Infraestructura\Escuchas\RecalcularAlertaAcademica;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Tests\TestCase;

/** Alerta de riesgo de baja académica (RN-21, HU-22): tabla de docs/hitos/f1-h6.md §7.1 y escucha de DA-88. */
test('RN-21 (§7.1 fila 1): en_riesgo sin alerta abierta, el comando crea la alerta', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    alertasRecalcular();

    $alerta = alertasUnica($m['j1'], ALERTA_ACADEMICA);
    expect($alerta->cerrada_en)->toBeNull()
        ->and($alerta->equipo_id)->toBeNull();
});

test('RN-21 (§7.1 fila 2): en_riesgo con alerta abierta, el comando no la duplica', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');
    $existente = alertaInsertar($m['j1'], ALERTA_ACADEMICA);

    alertasRecalcular();

    $alerta = alertasUnica($m['j1'], ALERTA_ACADEMICA);
    expect($alerta->id)->toBe($existente)
        ->and($alerta->cerrada_en)->toBeNull();
});

test('RN-21 (§7.1 fila 3): en_observacion sin alerta abierta, no se crea', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_observacion');

    alertasRecalcular();

    expect(alertasFilas($m['j1'], ALERTA_ACADEMICA))->toBe([]);
});

test('RN-21 (§7.1 fila 4): en_observacion con alerta abierta, se cierra con cerrada_en', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_observacion');
    alertaInsertar($m['j1'], ALERTA_ACADEMICA);

    alertasRecalcular();

    expect(alertasUnica($m['j1'], ALERTA_ACADEMICA)->cerrada_en)->not->toBeNull();
});

test('RN-21 (§7.1 fila 5): sin_riesgo con alerta abierta, se cierra', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'sin_riesgo');
    alertaInsertar($m['j1'], ALERTA_ACADEMICA);

    alertasRecalcular();

    expect(alertasUnica($m['j1'], ALERTA_ACADEMICA)->cerrada_en)->not->toBeNull();
});

test('RN-21 (DA-88): CalificacionRegistrada recalcula la alerta académica y la abre', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    event(new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']));

    expect(alertasAbiertas($m['j1'], ALERTA_ACADEMICA))->toBe(1);
});

test('RN-21 (DA-88): AsistenciaDeClaseRegistrada recalcula la alerta académica y la abre', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    event(new AsistenciaDeClaseRegistrada($m['i1'], $m['m1'], $m['j1']));

    expect(alertasAbiertas($m['j1'], ALERTA_ACADEMICA))->toBe(1);
});

test('RN-21 (DA-88): JugadorInscrito recalcula la alerta académica y la abre', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    event(new JugadorInscrito($m['i1'], $m['m1'], $m['j1']));

    expect(alertasAbiertas($m['j1'], ALERTA_ACADEMICA))->toBe(1);
});

test('RN-21 (DA-88): CriterioDeMateriaModificado recalcula la alerta de los jugadores de la materia', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    event(new CriterioDeMateriaModificado($m['m1']));

    expect(alertasAbiertas($m['j1'], ALERTA_ACADEMICA))->toBe(1)
        ->and(alertasFilas($m['j3'], ALERTA_ACADEMICA))->toBe([]);
});

test('RN-21 (DA-88): el mismo evento dos veces no duplica la alerta', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');

    event(new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']));
    $primera = alertasUnica($m['j1'], ALERTA_ACADEMICA);
    event(new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']));

    $segunda = alertasUnica($m['j1'], ALERTA_ACADEMICA);
    expect($segunda->id)->toBe($primera->id)
        ->and($segunda->activada_en)->toBe($primera->activada_en);
});

test('RN-21 (DA-88): al subir el nivel por un evento, la alerta abierta se cierra', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    alertasNivel($m, 'en_riesgo');
    event(new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']));

    alertasNivel($m, 'sin_riesgo');
    event(new CalificacionRegistrada($m['i1'], $m['m1'], $m['j1']));

    expect(alertasUnica($m['j1'], ALERTA_ACADEMICA)->cerrada_en)->not->toBeNull();
});

test('P-08c (DA-99): un evento sobre un jugador en baja no abre la alerta académica', function () {
    /** @var TestCase $this */
    $m = alertasMundo();
    mundoCalificar($m['i5'], 'final', '5.00');

    event(new CalificacionRegistrada($m['i5'], $m['m1'], $m['j5']));

    expect(alertasFilas($m['j5']))->toBe([]);
});

test('DA-88: el escucha es de cola, se despacha después del commit y está registrado para los cuatro eventos', function () {
    /** @var TestCase $this */
    $escucha = new ReflectionClass(RecalcularAlertaAcademica::class);

    expect($escucha->implementsInterface(ShouldQueue::class))->toBeTrue()
        ->and($escucha->getProperty('afterCommit')->getDefaultValue())->toBeTrue()
        ->and($escucha->getProperty('tries')->getDefaultValue())->toBe(3);
    foreach ([CalificacionRegistrada::class, AsistenciaDeClaseRegistrada::class, CriterioDeMateriaModificado::class, JugadorInscrito::class] as $evento) {
        expect(Event::hasListeners($evento))->toBeTrue();
    }
});
