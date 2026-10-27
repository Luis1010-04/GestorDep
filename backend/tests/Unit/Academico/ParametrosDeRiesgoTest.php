<?php

use Src\Academico\Dominio\ParametrosDeRiesgo;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

function parametrosDePrueba(string $sinRiesgo = '7.50', string $observacion = '7.00', int $reprobadasObservacion = 1, int $reprobadasRiesgo = 2): ParametrosDeRiesgo
{
    return new ParametrosDeRiesgo(
        NumeroDecimal::desdeTexto($sinRiesgo),
        NumeroDecimal::desdeTexto($observacion),
        $reprobadasObservacion,
        $reprobadasRiesgo,
    );
}

test('RN-04: los parámetros válidos exponen sus cuatro valores', function () {
    $parametros = parametrosDePrueba();

    expect($parametros->promedioSinRiesgoMinimo()->comoCadena())->toBe('7.50')
        ->and($parametros->promedioObservacionMinimo()->comoCadena())->toBe('7.00')
        ->and($parametros->reprobadasObservacion())->toBe(1)
        ->and($parametros->reprobadasRiesgo())->toBe(2);
});

test('RN-04: los umbrales iguales, el cero, el diez y reprobadas iguales son válidos', function () {
    expect(parametrosDePrueba('7.00', '7.00', 2, 2)->reprobadasRiesgo())->toBe(2)
        ->and(parametrosDePrueba('10.00', '0.00')->promedioSinRiesgoMinimo()->comoCadena())->toBe('10.00')
        ->and(parametrosDePrueba('10.00', '0.00')->promedioObservacionMinimo()->comoCadena())->toBe('0.00');
});

test('RN-04: parámetros incoherentes se rechazan', function (string $sinRiesgo, string $observacion, int $reprobadasObservacion, int $reprobadasRiesgo) {
    expect(fn () => parametrosDePrueba($sinRiesgo, $observacion, $reprobadasObservacion, $reprobadasRiesgo))
        ->toThrow(ReglaDeNegocioViolada::class, 'parametros_de_riesgo_invalidos');
})->with([
    'observación mayor que sin riesgo' => ['7.00', '7.01', 1, 2],
    'sin riesgo sobre diez' => ['10.01', '7.00', 1, 2],
    'observación bajo cero' => ['7.50', '-0.01', 1, 2],
    'reprobadas de observación en cero' => ['7.50', '7.00', 0, 2],
    'reprobadas de riesgo menor que las de observación' => ['7.50', '7.00', 2, 1],
]);
