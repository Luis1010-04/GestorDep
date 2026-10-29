<?php

use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\ConteoDeAsistencia;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\MateriaEvaluada;
use Src\Academico\Dominio\NivelDeRiesgo;
use Src\Academico\Dominio\ResumenAcademico;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * @param  list<string|null>  $calificaciones
 */
function resumenDePrueba(array $calificaciones, ?ConteoDeAsistencia $asistencia = null, string $minimo = '7.00'): ResumenAcademico
{
    $criterio = new CriterioDeAprobacion(AmbitoDeCriterio::General, NumeroDecimal::desdeTexto($minimo));
    $materias = array_map(
        fn (?string $calificacion) => new MateriaEvaluada($calificacion === null ? null : NumeroDecimal::desdeTexto($calificacion), $criterio),
        $calificaciones,
    );

    return ResumenAcademico::calcular($materias, $asistencia ?? new ConteoDeAsistencia(0, 0, 0), parametrosDePrueba());
}

test('RN-04 (§7.9 promedio fila 1): 8.00 y 9.00 dan 8.50 y el dato está completo', function () {
    $resumen = resumenDePrueba(['8.00', '9.00']);

    expect($resumen->promedio()->comoCadena())->toBe('8.50')
        ->and($resumen->materiasSinCalificar())->toBe(0)
        ->and($resumen->datoIncompleto())->toBeFalse();
});

test('RN-04 (§7.9 promedio fila 2): una materia sin calificar deja el dato incompleto', function () {
    $resumen = resumenDePrueba(['8.00', '9.00', null]);

    expect($resumen->promedio()->comoCadena())->toBe('8.50')
        ->and($resumen->materiasSinCalificar())->toBe(1)
        ->and($resumen->datoIncompleto())->toBeTrue();
});

test('RN-04 (§7.9 promedio fila 3): sin calificaciones el promedio y el nivel son nulos', function () {
    $resumen = resumenDePrueba([null, null]);

    expect($resumen->promedio())->toBeNull()
        ->and($resumen->nivelRiesgo())->toBeNull()
        ->and($resumen->materiasSinCalificar())->toBe(2)
        ->and($resumen->materiasReprobadas())->toBe(0)
        ->and($resumen->datoIncompleto())->toBeTrue();
});

test('RN-02 (§7.9 promedio fila 4): 7.49 y 7.50 dan 7.50 y ninguna reprobada', function () {
    $resumen = resumenDePrueba(['7.49', '7.50']);

    expect($resumen->promedio()->comoCadena())->toBe('7.50')
        ->and($resumen->materiasReprobadas())->toBe(0)
        ->and($resumen->datoIncompleto())->toBeFalse();
});

test('RN-02 (§7.9 promedio fila 5): 7.00, 7.00 y 8.00 dan 7.33', function () {
    $resumen = resumenDePrueba(['7.00', '7.00', '8.00']);

    expect($resumen->promedio()->comoCadena())->toBe('7.33')
        ->and($resumen->datoIncompleto())->toBeFalse();
});

test('RN-02 (§7.9 promedio fila 6): una sola materia con 6.00 da 6.00, una reprobada y en_riesgo', function () {
    $resumen = resumenDePrueba(['6.00']);

    expect($resumen->promedio()->comoCadena())->toBe('6.00')
        ->and($resumen->materiasReprobadas())->toBe(1)
        ->and($resumen->nivelRiesgo())->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 (§7.9 promedio fila 7): sin materias el promedio y el nivel son nulos y el dato está incompleto', function () {
    $resumen = resumenDePrueba([]);

    expect($resumen->promedio())->toBeNull()
        ->and($resumen->nivelRiesgo())->toBeNull()
        ->and($resumen->materiasReprobadas())->toBe(0)
        ->and($resumen->materiasSinCalificar())->toBe(0)
        ->and($resumen->datoIncompleto())->toBeTrue();
});

test('RN-04: las materias sin calificar no cuentan como reprobadas', function () {
    $resumen = resumenDePrueba(['9.00', null, null]);

    expect($resumen->materiasReprobadas())->toBe(0)
        ->and($resumen->materiasSinCalificar())->toBe(2)
        ->and($resumen->nivelRiesgo())->toBe(NivelDeRiesgo::SinRiesgo);
});

test('RN-04: dos reprobadas con buen promedio dan en_riesgo y una da en_observacion', function () {
    expect(resumenDePrueba(['6.00', '10.00', '10.00', '10.00'])->nivelRiesgo())->toBe(NivelDeRiesgo::EnObservacion)
        ->and(resumenDePrueba(['6.00', '6.50', '10.00', '10.00', '10.00', '10.00'])->nivelRiesgo())->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-05: la asistencia no entra en el nivel de riesgo', function () {
    $resumen = resumenDePrueba(['9.00'], new ConteoDeAsistencia(0, 0, 10));

    expect($resumen->porcentajeAsistencia()->comoCadena())->toBe('0.00')
        ->and($resumen->nivelRiesgo())->toBe(NivelDeRiesgo::SinRiesgo);
});

test('RN-05: el resumen copia el porcentaje de asistencia y es nulo sin registros', function () {
    expect(resumenDePrueba(['9.00'], new ConteoDeAsistencia(8, 0, 2))->porcentajeAsistencia()->comoCadena())->toBe('80.00')
        ->and(resumenDePrueba(['9.00'])->porcentajeAsistencia())->toBeNull();
});
