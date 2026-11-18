<?php

use Src\Alertas\Dominio\AccionSobreAlerta;
use Src\Alertas\Dominio\AcumulacionDeSanciones;
use Src\Alertas\Dominio\AlertaAcademica;
use Src\Alertas\Dominio\DecisionDeAlerta;
use Src\Alertas\Dominio\InasistenciaAEntrenamiento;
use Src\Alertas\Dominio\ParametrosDeSanciones;
use Src\Alertas\Dominio\ResultadoDeEvaluacion;
use Src\Alertas\Dominio\RiesgoDeBajaDeportiva;
use Src\Alertas\Dominio\TarjetasDePartido;
use Src\Alertas\Dominio\TarjetasRegistradas;
use Src\Alertas\Dominio\VentanaDeFaltas;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

const ALERTAS_FECHA_DE_CALCULO = '2026-11-15';
const ALERTAS_TEMPORADA = 'temporada-actual';
const ALERTAS_TEMPORADA_ANTERIOR = 'temporada-anterior';

/** Parámetros de fábrica de DA-100 ([Propuesta], AB-13): 3 amarillas, 1 roja, 3 faltas en 30 días. */
function alertasParametrosDeSanciones(): ParametrosDeSanciones
{
    return new ParametrosDeSanciones(tarjetasAmarillas: 3, tarjetasRojas: 1, faltasUmbral: 3, faltasVentanaDias: 30);
}

/** Una falta (o falta justificada) tantos días antes de la fecha de cálculo. */
function alertasInasistencia(int $diasAntes, bool $justificada = false): InasistenciaAEntrenamiento
{
    return new InasistenciaAEntrenamiento(
        (new DateTimeImmutable(ALERTAS_FECHA_DE_CALCULO))->modify("-{$diasAntes} days"),
        $justificada,
    );
}

/**
 * @param  list<TarjetasDePartido>  $tarjetas
 * @param  list<InasistenciaAEntrenamiento>  $inasistencias
 */
function alertasAcumulacion(array $tarjetas = [], array $inasistencias = [], string $temporada = ALERTAS_TEMPORADA, ?ParametrosDeSanciones $parametros = null): bool
{
    return AcumulacionDeSanciones::seCumple(
        $tarjetas,
        $temporada,
        $inasistencias,
        new DateTimeImmutable(ALERTAS_FECHA_DE_CALCULO),
        $parametros ?? alertasParametrosDeSanciones(),
    );
}

/** La decisión de RN-21 para un nivel de riesgo, con `en_riesgo` como nivel que activa (DA-100). */
function alertasDecisionAcademica(?string $nivel, bool $hayAbierta): AccionSobreAlerta
{
    return DecisionDeAlerta::decidir(AlertaAcademica::seCumple($nivel, 'en_riesgo'), $hayAbierta);
}

test('RN-21 (§7.1 fila 1): en_riesgo sin alerta abierta crea la alerta', function () {
    expect(alertasDecisionAcademica('en_riesgo', false))->toBe(AccionSobreAlerta::Abrir);
});

test('RN-21 (§7.1 fila 2): en_riesgo con alerta abierta no la duplica', function () {
    expect(alertasDecisionAcademica('en_riesgo', true))->toBe(AccionSobreAlerta::Ninguna);
});

test('RN-21 (§7.1 fila 3): en_observacion sin alerta abierta no crea ninguna', function () {
    expect(alertasDecisionAcademica('en_observacion', false))->toBe(AccionSobreAlerta::Ninguna);
});

test('RN-21 (§7.1 fila 4): en_observacion con alerta abierta la cierra', function () {
    expect(alertasDecisionAcademica('en_observacion', true))->toBe(AccionSobreAlerta::Cerrar);
});

test('RN-21 (§7.1 fila 5): sin_riesgo con alerta abierta la cierra', function () {
    expect(alertasDecisionAcademica('sin_riesgo', true))->toBe(AccionSobreAlerta::Cerrar);
});

test('RN-21: sin nivel de riesgo (sin datos) no se cumple, y el nivel que activa viene del parámetro, no del código', function () {
    expect(AlertaAcademica::seCumple(null, 'en_riesgo'))->toBeFalse()
        ->and(alertasDecisionAcademica(null, true))->toBe(AccionSobreAlerta::Cerrar)
        ->and(AlertaAcademica::seCumple('en_observacion', 'en_observacion'))->toBeTrue()
        ->and(AlertaAcademica::seCumple('en_riesgo', 'en_observacion'))->toBeFalse();
});

test('RN-09 (§7.3 fila 1): 1 tarjeta roja en la temporada activa la acumulación', function () {
    expect(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA, 0, 1)]))->toBeTrue();
});

test('RN-09 (§7.3 fila 2): 2 tarjetas amarillas en la temporada no la activan', function () {
    expect(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA, 1, 0), new TarjetasDePartido(ALERTAS_TEMPORADA, 1, 0)]))->toBeFalse();
});

test('RN-09 (§7.3 fila 3): 3 tarjetas amarillas en la temporada la activan', function () {
    $tarjetas = [new TarjetasDePartido(ALERTAS_TEMPORADA, 1, 0), new TarjetasDePartido(ALERTAS_TEMPORADA, 2, 0)];

    expect(alertasAcumulacion(tarjetas: $tarjetas))->toBeTrue();
});

test('RN-09 (§7.3 fila 4): 3 faltas en 30 días la activan, con la más antigua en el primer día de la ventana', function () {
    expect(alertasAcumulacion(inasistencias: [alertasInasistencia(29), alertasInasistencia(10), alertasInasistencia(0)]))->toBeTrue();
});

test('RN-09 (§7.3 fila 5): 2 faltas y 1 falta justificada en 30 días no la activan', function () {
    expect(alertasAcumulacion(inasistencias: [alertasInasistencia(20), alertasInasistencia(10), alertasInasistencia(5, justificada: true)]))->toBeFalse();
});

test('RN-09 (§7.3 fila 6): 3 faltas repartidas en 31 días no la activan', function () {
    expect(alertasAcumulacion(inasistencias: [alertasInasistencia(30), alertasInasistencia(15), alertasInasistencia(0)]))->toBeFalse();
});

test('RN-09 (§7.3 fila 7): 1 tarjeta roja de la temporada anterior no la activa', function () {
    expect(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA_ANTERIOR, 0, 1)]))->toBeFalse();
});

test('RN-09 (§7.3 fila 8): alerta abierta por 3 faltas; cuando la más antigua sale de la ventana, se cierra', function () {
    $faltas = fn (int $masAntigua) => [alertasInasistencia($masAntigua), alertasInasistencia(10), alertasInasistencia(5)];

    expect(DecisionDeAlerta::decidir(alertasAcumulacion(inasistencias: $faltas(29)), hayAbierta: true))->toBe(AccionSobreAlerta::Ninguna)
        ->and(DecisionDeAlerta::decidir(alertasAcumulacion(inasistencias: $faltas(30)), hayAbierta: true))->toBe(AccionSobreAlerta::Cerrar);
});

test('RN-09 (§7.3 fila 9): alerta abierta por 1 tarjeta roja; tras el fin de la temporada, se cierra', function () {
    $roja = [new TarjetasDePartido(ALERTAS_TEMPORADA_ANTERIOR, 0, 1)];

    expect(DecisionDeAlerta::decidir(alertasAcumulacion(tarjetas: $roja, temporada: ALERTAS_TEMPORADA_ANTERIOR), hayAbierta: true))->toBe(AccionSobreAlerta::Ninguna)
        ->and(DecisionDeAlerta::decidir(alertasAcumulacion(tarjetas: $roja, temporada: ALERTAS_TEMPORADA), hayAbierta: true))->toBe(AccionSobreAlerta::Cerrar);
});

test('RN-09: las tarjetas amarillas y las faltas no se suman entre sí, y las de otra temporada no cuentan', function () {
    $dosAmarillas = [new TarjetasDePartido(ALERTAS_TEMPORADA, 2, 0), new TarjetasDePartido(ALERTAS_TEMPORADA_ANTERIOR, 5, 0)];

    expect(alertasAcumulacion(tarjetas: $dosAmarillas, inasistencias: [alertasInasistencia(3), alertasInasistencia(2)]))->toBeFalse()
        ->and(alertasAcumulacion(tarjetas: [...$dosAmarillas, new TarjetasDePartido(ALERTAS_TEMPORADA, 1, 0)]))->toBeTrue();
});

test('RN-09: la ventana de faltas son los días naturales que terminan en la fecha de cálculo, inclusive, sin importar la hora', function () {
    $calculo = new DateTimeImmutable('2026-11-15 02:00:00');
    $falta = fn (string $momento) => new InasistenciaAEntrenamiento(new DateTimeImmutable($momento), false);

    expect(VentanaDeFaltas::contar([$falta('2026-10-17 23:59:59'), $falta('2026-11-15 18:00:00')], $calculo, 30))->toBe(2)
        ->and(VentanaDeFaltas::contar([$falta('2026-10-16 23:59:59')], $calculo, 30))->toBe(0)
        ->and(VentanaDeFaltas::contar([$falta('2026-11-16 00:00:00')], $calculo, 30))->toBe(0)
        ->and(VentanaDeFaltas::contar([$falta('2026-11-15 00:00:00')], $calculo, 1))->toBe(1)
        ->and(VentanaDeFaltas::contar([$falta('2026-11-14 23:59:59')], $calculo, 1))->toBe(0)
        ->and(VentanaDeFaltas::contar([new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-11-10'), true)], $calculo, 30))->toBe(0);
});

test('RN-09: los umbrales vienen de los parámetros, no del código (RNF-09)', function () {
    $estrictos = new ParametrosDeSanciones(tarjetasAmarillas: 2, tarjetasRojas: 2, faltasUmbral: 1, faltasVentanaDias: 7);

    expect(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA, 2, 0)], parametros: $estrictos))->toBeTrue()
        ->and(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA, 1, 1)], parametros: $estrictos))->toBeFalse()
        ->and(alertasAcumulacion(tarjetas: [new TarjetasDePartido(ALERTAS_TEMPORADA, 0, 1), new TarjetasDePartido(ALERTAS_TEMPORADA, 0, 1)], parametros: $estrictos))->toBeTrue()
        ->and(alertasAcumulacion(inasistencias: [alertasInasistencia(6)], parametros: $estrictos))->toBeTrue()
        ->and(alertasAcumulacion(inasistencias: [alertasInasistencia(7)], parametros: $estrictos))->toBeFalse();
});

test('RN-09: unos parámetros de sanciones sin sentido se rechazan', function (int $amarillas, int $rojas, int $faltas, int $dias) {
    expect(fn () => new ParametrosDeSanciones($amarillas, $rojas, $faltas, $dias))
        ->toThrow(ReglaDeNegocioViolada::class, 'parametros_de_sanciones_invalidos');
})->with([
    'amarillas en cero' => [0, 1, 3, 30],
    'rojas en cero' => [3, 0, 3, 30],
    'faltas en cero' => [3, 1, 0, 30],
    'ventana en cero' => [3, 1, 3, 0],
]);

test('RN-09: los parámetros mínimos válidos se aceptan y exponen sus valores', function () {
    $minimos = new ParametrosDeSanciones(1, 1, 1, 1);

    expect([$minimos->tarjetasAmarillas(), $minimos->tarjetasRojas(), $minimos->faltasUmbral(), $minimos->faltasVentanaDias()])->toBe([1, 1, 1, 1]);
});

test('RN-09: un registro de tarjetas no admite cantidades negativas', function (int $amarillas, int $rojas) {
    expect(fn () => new TarjetasDePartido(ALERTAS_TEMPORADA, $amarillas, $rojas))
        ->toThrow(ReglaDeNegocioViolada::class, 'tarjetas_invalidas');
})->with([[-1, 0], [0, -1]]);

test('RN-10 (§7.4 fila 1): no_pasa sin sanciones acumuladas activa el riesgo de baja deportiva', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::NoPasa, hayAcumulacionDeSanciones: false))->toBeTrue();
});

test('RN-10 (§7.4 fila 2): pasa con sanciones acumuladas lo activa', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::Pasa, hayAcumulacionDeSanciones: true))->toBeTrue();
});

test('RN-10 (§7.4 fila 3): pasa sin sanciones acumuladas no lo activa', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::Pasa, hayAcumulacionDeSanciones: false))->toBeFalse();
});

test('RN-10 (§7.4 fila 4): datos_insuficientes sin sanciones acumuladas no lo activa', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::DatosInsuficientes, hayAcumulacionDeSanciones: false))->toBeFalse();
});

test('RN-10 (§7.4 fila 5): datos_insuficientes con sanciones acumuladas lo activa', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::DatosInsuficientes, hayAcumulacionDeSanciones: true))->toBeTrue();
});

test('RN-10: no_pasa con sanciones acumuladas también lo activa, y los resultados son los tres de RN-08', function () {
    expect(RiesgoDeBajaDeportiva::seCumple(ResultadoDeEvaluacion::NoPasa, hayAcumulacionDeSanciones: true))->toBeTrue()
        ->and(array_map(fn (ResultadoDeEvaluacion $resultado): string => $resultado->value, ResultadoDeEvaluacion::cases()))
        ->toBe(['pasa', 'no_pasa', 'datos_insuficientes']);
});

test('HU-22: una alerta se abre si la condición se cumple y no hay abierta, y se cierra si deja de cumplirse', function (bool $seCumple, bool $hayAbierta, AccionSobreAlerta $esperada) {
    expect(DecisionDeAlerta::decidir($seCumple, $hayAbierta))->toBe($esperada);
})->with([
    'se cumple, sin abierta' => [true, false, AccionSobreAlerta::Abrir],
    'se cumple, con abierta' => [true, true, AccionSobreAlerta::Ninguna],
    'no se cumple, sin abierta' => [false, false, AccionSobreAlerta::Ninguna],
    'no se cumple, con abierta' => [false, true, AccionSobreAlerta::Cerrar],
]);

test('DA-102: con varios entrenadores en el equipo basta que la evaluación de uno sea no_pasa', function (array $porEntrenador, ResultadoDeEvaluacion $esperado, bool $activa) {
    /** @var list<ResultadoDeEvaluacion> $porEntrenador */
    $resultado = ResultadoDeEvaluacion::delEquipo($porEntrenador);

    expect($resultado)->toBe($esperado)
        ->and(RiesgoDeBajaDeportiva::seCumple($resultado, hayAcumulacionDeSanciones: false))->toBe($activa);
})->with([
    'pasa y no_pasa' => [[ResultadoDeEvaluacion::Pasa, ResultadoDeEvaluacion::NoPasa], ResultadoDeEvaluacion::NoPasa, true],
    'no_pasa y datos_insuficientes' => [[ResultadoDeEvaluacion::NoPasa, ResultadoDeEvaluacion::DatosInsuficientes], ResultadoDeEvaluacion::NoPasa, true],
    'datos_insuficientes, pasa y no_pasa' => [[ResultadoDeEvaluacion::DatosInsuficientes, ResultadoDeEvaluacion::Pasa, ResultadoDeEvaluacion::NoPasa], ResultadoDeEvaluacion::NoPasa, true],
    'pasa y pasa' => [[ResultadoDeEvaluacion::Pasa, ResultadoDeEvaluacion::Pasa], ResultadoDeEvaluacion::Pasa, false],
    'datos_insuficientes y pasa' => [[ResultadoDeEvaluacion::DatosInsuficientes, ResultadoDeEvaluacion::Pasa], ResultadoDeEvaluacion::Pasa, false],
    'solo datos_insuficientes' => [[ResultadoDeEvaluacion::DatosInsuficientes, ResultadoDeEvaluacion::DatosInsuficientes], ResultadoDeEvaluacion::DatosInsuficientes, false],
    'un solo entrenador que no pasa' => [[ResultadoDeEvaluacion::NoPasa], ResultadoDeEvaluacion::NoPasa, true],
    'un solo entrenador que pasa' => [[ResultadoDeEvaluacion::Pasa], ResultadoDeEvaluacion::Pasa, false],
]);

test('DA-107 (RN-08, RN-10): sin ninguna evaluación guardada el resultado es datos_insuficientes y solo las sanciones activan el riesgo', function () {
    $sinEvaluaciones = ResultadoDeEvaluacion::delEquipo([]);

    expect($sinEvaluaciones)->toBe(ResultadoDeEvaluacion::DatosInsuficientes)
        ->and(RiesgoDeBajaDeportiva::seCumple($sinEvaluaciones, hayAcumulacionDeSanciones: false))->toBeFalse()
        ->and(RiesgoDeBajaDeportiva::seCumple($sinEvaluaciones, hayAcumulacionDeSanciones: true))->toBeTrue();
});

/**
 * @param  list<TarjetasDePartido>  $tarjetas
 * @return list<array{string, int, int}>
 */
function alertasTarjetasComoArreglo(array $tarjetas): array
{
    return array_map(fn (TarjetasDePartido $t): array => [$t->temporadaId(), $t->amarillas(), $t->rojas()], $tarjetas);
}

test('DA-103 (§7.6): la misma tarjeta amarilla registrada por dos entrenadores en un partido cuenta una, no dos', function () {
    $registros = [
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, 1, 0),
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, 1, 0),
    ];

    expect(alertasTarjetasComoArreglo(TarjetasDePartido::elMayorPorPartido($registros)))->toBe([[ALERTAS_TEMPORADA, 1, 0]]);
});

test('DA-103: por partido y tipo de tarjeta cuenta el mayor número registrado por un solo entrenador', function () {
    $registros = [
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, 2, 0),
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, 1, 1),
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, 0, 0),
    ];

    // El mayor de amarillas (2) y el mayor de rojas (1) pueden venir de entrenadores distintos; no se suman entre sí.
    expect(alertasTarjetasComoArreglo(TarjetasDePartido::elMayorPorPartido($registros)))->toBe([[ALERTAS_TEMPORADA, 2, 1]]);
});

test('DA-103: los partidos distintos no se mezclan y cada uno conserva su temporada', function () {
    $registros = [
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA_ANTERIOR, 0, 1),
        new TarjetasRegistradas('partido-2', ALERTAS_TEMPORADA, 1, 0),
        new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA_ANTERIOR, 0, 1),
        new TarjetasRegistradas('partido-3', ALERTAS_TEMPORADA, 1, 0),
        new TarjetasRegistradas('partido-2', ALERTAS_TEMPORADA, 1, 0),
    ];

    expect(alertasTarjetasComoArreglo(TarjetasDePartido::elMayorPorPartido($registros)))
        ->toBe([[ALERTAS_TEMPORADA_ANTERIOR, 0, 1], [ALERTAS_TEMPORADA, 1, 0], [ALERTAS_TEMPORADA, 1, 0]])
        ->and(TarjetasDePartido::elMayorPorPartido([]))->toBe([]);
});

test('DA-103 con RN-09: dos partidos con la misma amarilla registrada por dos entrenadores suman 2 y no activan; un tercero sí', function () {
    $dosVeces = fn (string $partido) => [new TarjetasRegistradas($partido, ALERTAS_TEMPORADA, 1, 0), new TarjetasRegistradas($partido, ALERTAS_TEMPORADA, 1, 0)];
    $dosPartidos = [...$dosVeces('partido-1'), ...$dosVeces('partido-2')];

    expect(alertasAcumulacion(tarjetas: TarjetasDePartido::elMayorPorPartido($dosPartidos)))->toBeFalse()
        ->and(alertasAcumulacion(tarjetas: TarjetasDePartido::elMayorPorPartido([...$dosPartidos, ...$dosVeces('partido-3')])))->toBeTrue();
});

test('DA-103: un registro de tarjetas de un entrenador no admite cantidades negativas', function (int $amarillas, int $rojas) {
    expect(fn () => new TarjetasRegistradas('partido-1', ALERTAS_TEMPORADA, $amarillas, $rojas))
        ->toThrow(ReglaDeNegocioViolada::class, 'tarjetas_invalidas');
})->with([[-1, 0], [0, -1]]);

test('RN-09 (P-08b): sin temporada que contenga la fecha de cálculo no se cuentan tarjetas, y las faltas sí', function () {
    $calculo = new DateTimeImmutable(ALERTAS_FECHA_DE_CALCULO);
    $roja = [new TarjetasDePartido(ALERTAS_TEMPORADA, 3, 1)];
    $tresFaltas = [alertasInasistencia(2), alertasInasistencia(1), alertasInasistencia(0)];

    expect(AcumulacionDeSanciones::seCumple($roja, null, [], $calculo, alertasParametrosDeSanciones()))->toBeFalse()
        ->and(AcumulacionDeSanciones::seCumple($roja, null, $tresFaltas, $calculo, alertasParametrosDeSanciones()))->toBeTrue();
});
