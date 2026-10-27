<?php

use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

function dCri(string $texto): NumeroDecimal
{
    return NumeroDecimal::desdeTexto($texto);
}

test('RN-01: una calificación igual al mínimo aprueba y una menor reprueba', function () {
    $criterio = new CriterioDeAprobacion(AmbitoDeCriterio::General, dCri('7.00'));

    expect($criterio->aprueba(dCri('7.00')))->toBeTrue()
        ->and($criterio->aprueba(dCri('7.01')))->toBeTrue()
        ->and($criterio->aprueba(dCri('6.99')))->toBeFalse();
});

test('RN-03: el criterio propio prevalece y el resultado indica que es de la materia', function () {
    $general = new CriterioDeAprobacion(AmbitoDeCriterio::General, dCri('7.00'));
    $propio = new CriterioDeAprobacion(AmbitoDeCriterio::Materia, dCri('8.00'));

    $resuelto = CriterioDeAprobacion::resolver($propio, $general);

    expect($resuelto)->toBe($propio)
        ->and($resuelto->aplicaA())->toBe(AmbitoDeCriterio::Materia)
        ->and($resuelto->calificacionMinima()->comoCadena())->toBe('8.00');
});

test('RN-03: sin criterio propio se aplica el general y así lo indica', function () {
    $general = new CriterioDeAprobacion(AmbitoDeCriterio::General, dCri('7.00'));

    $resuelto = CriterioDeAprobacion::resolver(null, $general);

    expect($resuelto)->toBe($general)
        ->and($resuelto->aplicaA())->toBe(AmbitoDeCriterio::General);
});

test('RN-03: el criterio propio debe ser de ámbito materia y el general de ámbito general', function () {
    $general = new CriterioDeAprobacion(AmbitoDeCriterio::General, dCri('7.00'));
    $materia = new CriterioDeAprobacion(AmbitoDeCriterio::Materia, dCri('8.00'));

    expect(fn () => CriterioDeAprobacion::resolver($general, $general))
        ->toThrow(ReglaDeNegocioViolada::class, 'criterio_de_ambito_invalido')
        ->and(fn () => CriterioDeAprobacion::resolver(null, $materia))
        ->toThrow(ReglaDeNegocioViolada::class, 'criterio_de_ambito_invalido')
        ->and(CriterioDeAprobacion::resolver($materia, $general))->toBe($materia);
});
