<?php

use Src\Academico\Dominio\NivelDeRiesgo;
use Src\Academico\Dominio\ParametrosDeRiesgo;
use Src\Academico\Dominio\RiesgoAcademico;
use Src\Compartido\Dominio\NumeroDecimal;

function nivelDeRiesgo(?string $promedio, int $reprobadas, ?ParametrosDeRiesgo $parametros = null): ?NivelDeRiesgo
{
    return RiesgoAcademico::nivel(
        $promedio === null ? null : NumeroDecimal::desdeTexto($promedio),
        $reprobadas,
        $parametros ?? parametrosDePrueba(),
    );
}

test('RN-04 (§7.9 riesgo fila 1): 8.00 sin reprobadas es sin_riesgo', function () {
    expect(nivelDeRiesgo('8.00', 0))->toBe(NivelDeRiesgo::SinRiesgo);
});

test('RN-04 (§7.9 riesgo fila 2): 7.50 sin reprobadas es sin_riesgo', function () {
    expect(nivelDeRiesgo('7.50', 0))->toBe(NivelDeRiesgo::SinRiesgo);
});

test('RN-04 (§7.9 riesgo fila 3): 7.495 se redondea a 7.50 y es sin_riesgo', function () {
    expect(nivelDeRiesgo('7.495', 0))->toBe(NivelDeRiesgo::SinRiesgo);
});

test('RN-04 (§7.9 riesgo fila 4): 7.49 sin reprobadas es en_observacion', function () {
    expect(nivelDeRiesgo('7.49', 0))->toBe(NivelDeRiesgo::EnObservacion);
});

test('RN-04 (§7.9 riesgo fila 5): 7.00 sin reprobadas es en_observacion', function () {
    expect(nivelDeRiesgo('7.00', 0))->toBe(NivelDeRiesgo::EnObservacion);
});

test('RN-04 (§7.9 riesgo fila 6): 8.50 con 1 reprobada es en_observacion', function () {
    expect(nivelDeRiesgo('8.50', 1))->toBe(NivelDeRiesgo::EnObservacion);
});

test('RN-04 (§7.9 riesgo fila 7): 6.99 sin reprobadas es en_riesgo', function () {
    expect(nivelDeRiesgo('6.99', 0))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 (§7.9 riesgo fila 8): 8.50 con 2 reprobadas es en_riesgo', function () {
    expect(nivelDeRiesgo('8.50', 2))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 (§7.9 riesgo fila 9): 7.20 con 2 reprobadas es en_riesgo, prevalece el más grave', function () {
    expect(nivelDeRiesgo('7.20', 2))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 (§7.9 riesgo fila 10): 6.50 con 1 reprobada es en_riesgo', function () {
    expect(nivelDeRiesgo('6.50', 1))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 (§7.9 riesgo fila 11): sin promedio el nivel es nulo', function () {
    expect(nivelDeRiesgo(null, 0))->toBeNull()
        ->and(nivelDeRiesgo(null, 3))->toBeNull();
});

test('RN-04 / RNF-09 (§7.9 riesgo fila 12): los umbrales de promedio vienen de los parámetros', function () {
    $parametros = parametrosDePrueba('8.00', '7.50');

    expect(nivelDeRiesgo('7.80', 0, $parametros))->toBe(NivelDeRiesgo::EnObservacion)
        ->and(nivelDeRiesgo('8.00', 0, $parametros))->toBe(NivelDeRiesgo::SinRiesgo)
        ->and(nivelDeRiesgo('7.49', 0, $parametros))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04 / RNF-09 (§7.9 riesgo fila 13): el umbral de reprobadas viene del parámetro', function () {
    $parametros = parametrosDePrueba(reprobadasRiesgo: 3);

    expect(nivelDeRiesgo('7.80', 2, $parametros))->toBe(NivelDeRiesgo::EnObservacion)
        ->and(nivelDeRiesgo('7.80', 3, $parametros))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04: el umbral de reprobadas de observación viene del parámetro', function () {
    $parametros = parametrosDePrueba(reprobadasObservacion: 2, reprobadasRiesgo: 3);

    expect(nivelDeRiesgo('8.00', 1, $parametros))->toBe(NivelDeRiesgo::SinRiesgo)
        ->and(nivelDeRiesgo('8.00', 2, $parametros))->toBe(NivelDeRiesgo::EnObservacion);
});

test('RN-04: las fronteras de 1 y 2 reprobadas con promedio sano', function () {
    expect(nivelDeRiesgo('9.00', 0))->toBe(NivelDeRiesgo::SinRiesgo)
        ->and(nivelDeRiesgo('9.00', 1))->toBe(NivelDeRiesgo::EnObservacion)
        ->and(nivelDeRiesgo('9.00', 2))->toBe(NivelDeRiesgo::EnRiesgo)
        ->and(nivelDeRiesgo('9.00', 5))->toBe(NivelDeRiesgo::EnRiesgo);
});

test('RN-04: el promedio se redondea a dos decimales antes de comparar', function () {
    expect(nivelDeRiesgo('6.995', 0))->toBe(NivelDeRiesgo::EnObservacion)
        ->and(nivelDeRiesgo('6.994', 0))->toBe(NivelDeRiesgo::EnRiesgo);
});
