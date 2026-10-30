<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\CalculoDeDesempeno;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\EvaluacionCalculada;
use Src\Rendimiento\Dominio\EvaluacionDeRendimiento;
use Src\Rendimiento\Dominio\ResultadoDeRendimiento;

/**
 * El cálculo de RN-08 (docs/hitos/f1-h4.md §3.3, v1.0.5), sin base de datos. Las calificaciones llegan de la más
 * reciente a la más antigua.
 *
 * @param  list<int>  $calificaciones
 */
function desempenoDe(array $calificaciones, string $minima = '6.00', int $ventana = 5): EvaluacionCalculada
{
    return CalculoDeDesempeno::evaluar(new CriterioDeEvaluacion(NumeroDecimal::desdeTexto($minima), $ventana), $calificaciones);
}

/** @return array{resultado: string, promedio: string|null, partidos: int} */
function desempenoResumen(EvaluacionCalculada $evaluacion): array
{
    return ['resultado' => $evaluacion->resultado()->value, 'promedio' => $evaluacion->promedio()?->comoCadena(), 'partidos' => $evaluacion->partidosConsiderados()];
}

test('HU-16 (§7.3 fila 1): con cuatro de cinco calificaciones el resultado es datos_insuficientes, sin promedio', function () {
    expect(desempenoResumen(desempenoDe([7, 7, 8, 6])))->toBe(['resultado' => 'datos_insuficientes', 'promedio' => null, 'partidos' => 4]);
});

test('HU-16 (§7.3 fila 2): con cinco calificaciones (7, 7, 8, 6, 5) el promedio es 6.60 y pasa', function () {
    expect(desempenoResumen(desempenoDe([7, 7, 8, 6, 5])))->toBe(['resultado' => 'pasa', 'promedio' => '6.60', 'partidos' => 5]);
});

test('HU-16 (§7.3 fila 3): con (5, 5, 6, 6, 5) el promedio es 5.40 y no pasa', function () {
    expect(desempenoResumen(desempenoDe([5, 5, 6, 6, 5])))->toBe(['resultado' => 'no_pasa', 'promedio' => '5.40', 'partidos' => 5]);
});

test('HU-16 (§7.3 fila 4): un promedio exactamente igual al umbral pasa', function () {
    expect(desempenoResumen(desempenoDe([6, 6, 6, 6, 6])))->toBe(['resultado' => 'pasa', 'promedio' => '6.00', 'partidos' => 5]);
});

test('RN-08: con más de N calificaciones solo cuentan las N más recientes', function () {
    expect(desempenoResumen(desempenoDe([5, 5, 5, 5, 5, 10, 10])))->toBe(['resultado' => 'no_pasa', 'promedio' => '5.00', 'partidos' => 5])
        ->and(desempenoResumen(desempenoDe([10, 10, 10, 10, 10, 1])))->toBe(['resultado' => 'pasa', 'promedio' => '10.00', 'partidos' => 5]);
});

test('RN-08 (v1.0.5): se compara el promedio ya redondeado: 5.875 se redondea a 5.88 y con umbral 5.88 pasa', function () {
    $calificaciones = [6, 6, 6, 6, 6, 6, 6, 5];

    expect(desempenoResumen(desempenoDe($calificaciones, '5.88', 8)))->toBe(['resultado' => 'pasa', 'promedio' => '5.88', 'partidos' => 8])
        ->and(desempenoResumen(desempenoDe($calificaciones, '5.89', 8)))->toBe(['resultado' => 'no_pasa', 'promedio' => '5.88', 'partidos' => 8]);
});

test('RN-08: un umbral con decimales compara en el borde', function () {
    expect(desempenoDe([7, 6], '6.50', 2)->resultado())->toBe(ResultadoDeRendimiento::Pasa)
        ->and(desempenoDe([7, 6], '6.51', 2)->resultado())->toBe(ResultadoDeRendimiento::NoPasa);
});

test('RN-08: con ventana 1 cuenta solo la última; sin calificaciones son datos insuficientes con cero partidos', function () {
    expect(desempenoResumen(desempenoDe([4, 10], ventana: 1)))->toBe(['resultado' => 'no_pasa', 'promedio' => '4.00', 'partidos' => 1])
        ->and(desempenoResumen(desempenoDe([])))->toBe(['resultado' => 'datos_insuficientes', 'promedio' => null, 'partidos' => 0]);
});

test('RN-08: los códigos de resultado son los del CHECK de evaluaciones_rendimiento', function () {
    expect(array_map(fn (ResultadoDeRendimiento $resultado) => $resultado->value, ResultadoDeRendimiento::cases()))
        ->toBe(['pasa', 'no_pasa', 'datos_insuficientes']);
});

test('HU-16: una evaluación guarda el criterio aplicado, su procedencia, el cálculo y el instante', function () {
    [$id, $jugador, $equipo, $entrenador, $umbral, $plantilla] = array_map(fn () => Identificador::generar(), range(1, 6));
    $criterio = new CriterioDeEvaluacion(NumeroDecimal::desdeTexto('7.00'), 3);
    $calculada = CalculoDeDesempeno::evaluar($criterio, [8, 8, 8]);
    $instante = new DateTimeImmutable('2026-09-20 15:00:00+00:00');

    $evaluacion = new EvaluacionDeRendimiento($id, $jugador, $equipo, $entrenador, $umbral, $plantilla, $criterio, $calculada, $instante);

    expect($evaluacion->id())->toBe($id)
        ->and($evaluacion->jugadorId())->toBe($jugador)
        ->and($evaluacion->equipoId())->toBe($equipo)
        ->and($evaluacion->entrenadorId())->toBe($entrenador)
        ->and($evaluacion->umbralId())->toBe($umbral)
        ->and($evaluacion->plantillaId())->toBe($plantilla)
        ->and($evaluacion->criterio())->toBe($criterio)
        ->and($evaluacion->calculada())->toBe($calculada)
        ->and($evaluacion->calculadaEn())->toBe($instante);
});
