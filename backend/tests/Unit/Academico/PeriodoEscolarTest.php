<?php

use Src\Academico\Dominio\PeriodoEscolar;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

function periodoDePrueba(string $inicio, string $fin): PeriodoEscolar
{
    $utc = new DateTimeZone('UTC');

    return new PeriodoEscolar(Identificador::generar(), '2026-1', new DateTimeImmutable($inicio, $utc), new DateTimeImmutable($fin, $utc), new DateTimeImmutable('2026-03-10 12:00:00', $utc));
}

test('HU-28: un periodo con fecha_fin posterior a fecha_inicio expone sus datos', function () {
    $periodo = periodoDePrueba('2026-01-12', '2026-01-13');

    expect($periodo->nombre())->toBe('2026-1')
        ->and($periodo->fechaInicio()->format('Y-m-d'))->toBe('2026-01-12')
        ->and($periodo->fechaFin()->format('Y-m-d'))->toBe('2026-01-13')
        ->and($periodo->actualizadoEn()->format('Y-m-d H:i:s'))->toBe('2026-03-10 12:00:00')
        ->and($periodo->id())->toBeInstanceOf(Identificador::class);
});

test('HU-28: una fecha_fin igual o anterior a fecha_inicio viola rango_de_fechas_invalido', function (string $inicio, string $fin) {
    expect(fn () => periodoDePrueba($inicio, $fin))->toThrow(ReglaDeNegocioViolada::class, 'rango_de_fechas_invalido');
})->with([
    'mismo día' => ['2026-01-12', '2026-01-12'],
    'un día antes' => ['2026-01-12', '2026-01-11'],
]);

test('HU-28: las fechas se comparan por día aunque traigan hora', function () {
    $utc = new DateTimeZone('UTC');

    expect(fn () => new PeriodoEscolar(
        Identificador::generar(), 'X',
        new DateTimeImmutable('2026-01-12 08:00:00', $utc), new DateTimeImmutable('2026-01-12 20:00:00', $utc),
        new DateTimeImmutable('2026-03-10', $utc),
    ))->toThrow(ReglaDeNegocioViolada::class, 'rango_de_fechas_invalido');
});
