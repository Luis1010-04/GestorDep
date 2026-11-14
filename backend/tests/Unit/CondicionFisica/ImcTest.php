<?php

use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Dominio\MedicionCorporal;
use Src\CondicionFisica\Dominio\RangoNoConfigurado;
use Src\CondicionFisica\Dominio\ValorFueraDeRango;

/** IMC que cabe en la columna (DA-71) y rango no configurado (DA-70). */
function imcComprobar(string $peso, string $estatura, string $maximo): ?ValorFueraDeRango
{
    try {
        MedicionCorporal::exigirImcGuardable(NumeroDecimal::desdeTexto($peso), NumeroDecimal::desdeTexto($estatura), NumeroDecimal::desdeTexto($maximo));
    } catch (ValorFueraDeRango $e) {
        return $e;
    }

    return null;
}

test('DA-71: un IMC igual al máximo de la columna se admite', function () {
    expect(imcComprobar('70.00', '100.0', '70.00'))->toBeNull()
        ->and(imcComprobar('200.00', '44.8', '999.99'))->toBeNull();
});

test('DA-71: un IMC por encima del máximo de la columna responde valor_fuera_de_rango en estatura_cm', function () {
    $error = imcComprobar('70.01', '100.0', '70.00');
    $desborde = imcComprobar('200.00', '44.7', '999.99');

    expect($error?->codigo())->toBe('valor_fuera_de_rango')
        ->and($error?->campo())->toBe('estatura_cm')
        ->and($desborde?->campo())->toBe('estatura_cm');
});

test('DA-71: el IMC que se valida es el mismo que se guarda', function () {
    $peso = NumeroDecimal::desdeTexto('200.00');
    $estatura = NumeroDecimal::desdeTexto('44.8');

    expect(MedicionCorporal::calcularImc($peso, $estatura)->comoCadena(2))->toBe('996.49');
});

test('DA-70: un rango no configurado lleva su código', function () {
    expect((new RangoNoConfigurado)->codigo())->toBe('rango_no_configurado');
});
