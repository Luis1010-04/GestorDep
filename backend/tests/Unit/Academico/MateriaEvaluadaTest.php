<?php

use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CalificacionDeMateria;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\MateriaEvaluada;
use Src\Compartido\Dominio\NumeroDecimal;

function dMat(string $texto): NumeroDecimal
{
    return NumeroDecimal::desdeTexto($texto);
}

function criterioDeMateria(AmbitoDeCriterio $ambito, string $minimo): CriterioDeAprobacion
{
    return new CriterioDeAprobacion($ambito, dMat($minimo));
}

test('RN-03 (§7.9 aprobada fila 1): 6.99 con criterio general 7.00 reprueba', function () {
    $materia = new MateriaEvaluada(dMat('6.99'), criterioDeMateria(AmbitoDeCriterio::General, '7.00'));

    expect($materia->aprobada())->toBeFalse()
        ->and($materia->criterio()->aplicaA())->toBe(AmbitoDeCriterio::General);
});

test('RN-01 (§7.9 aprobada fila 2): 7.00 con criterio general 7.00 aprueba', function () {
    $materia = new MateriaEvaluada(dMat('7.00'), criterioDeMateria(AmbitoDeCriterio::General, '7.00'));

    expect($materia->aprobada())->toBeTrue()
        ->and($materia->calificacion()->comoCadena())->toBe('7.00');
});

test('RN-03 (§7.9 aprobada fila 3): 6.50 con criterio propio 6.00 aprueba', function () {
    $materia = new MateriaEvaluada(dMat('6.50'), criterioDeMateria(AmbitoDeCriterio::Materia, '6.00'));

    expect($materia->aprobada())->toBeTrue()
        ->and($materia->criterio()->aplicaA())->toBe(AmbitoDeCriterio::Materia);
});

test('RN-03 (§7.9 aprobada fila 4): 7.50 con criterio propio 8.00 reprueba aunque pase el general', function () {
    $materia = new MateriaEvaluada(dMat('7.50'), criterioDeMateria(AmbitoDeCriterio::Materia, '8.00'));

    expect($materia->aprobada())->toBeFalse();
});

test('RN-03 (§7.9 aprobada fila 5): sin calificación no está aprobada ni reprobada', function () {
    $materia = new MateriaEvaluada(null, criterioDeMateria(AmbitoDeCriterio::General, '7.00'));

    expect($materia->aprobada())->toBeNull()
        ->and($materia->calificacion())->toBeNull();
});

test('DD-43 (§7.9 aprobada fila 6): parciales 6.99 y 7.00 dan 6.995, que se redondea a 7.00 y aprueba', function () {
    $calificacion = CalificacionDeMateria::calcular(null, [dMat('6.99'), dMat('7.00')]);
    $materia = new MateriaEvaluada($calificacion, criterioDeMateria(AmbitoDeCriterio::General, '7.00'));

    expect($materia->calificacion()->comoCadena())->toBe('7.00')
        ->and($materia->aprobada())->toBeTrue();
});
