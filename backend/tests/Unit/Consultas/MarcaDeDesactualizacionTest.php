<?php

use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Consultas\Aplicacion\MarcaDeDesactualizacion;

/** Hoy de negocio: el 15 de noviembre de 2026 en America/Mexico_City (UTC−6). */
function marcaDeHoy(int $dias = 30): MarcaDeDesactualizacion
{
    return new MarcaDeDesactualizacion(new DateTimeImmutable('2026-11-15 10:00:00', new DateTimeZone('America/Mexico_City')), $dias);
}

test('RNF-04 (§7.7 fila 1, DD-15): una captura de hace 31 días está posiblemente desactualizada', function () {
    expect(marcaDeHoy()->de(new DateTimeImmutable('2026-10-15 18:00:00', new DateTimeZone('UTC'))))->toBeTrue();
});

test('RNF-04 (§7.7 fila 2, DD-15): una captura de hace 30 días no lo está', function () {
    expect(marcaDeHoy()->de(new DateTimeImmutable('2026-10-16 18:00:00', new DateTimeZone('UTC'))))->toBeFalse();
});

test('RNF-04: una captura de hoy o de ayer no está desactualizada', function (string $captura) {
    expect(marcaDeHoy()->de(new DateTimeImmutable($captura, new DateTimeZone('UTC'))))->toBeFalse();
})->with(['hoy' => ['2026-11-15 16:00:00'], 'ayer' => ['2026-11-14 16:00:00']]);

test('RNF-04: sin ninguna captura no hay nada que marcar como desactualizado', function () {
    expect(marcaDeHoy()->de(null))->toBeFalse();
});

test('RNF-04 (m5): el día de la captura se cuenta en la zona de hoy de negocio, no en UTC', function () {
    // 2026-10-16 05:59 UTC es el 15 de octubre a las 23:59 en México: 31 días antes de hoy. A las 06:00 UTC ya es el 16.
    expect(marcaDeHoy()->de(new DateTimeImmutable('2026-10-16 05:59:00', new DateTimeZone('UTC'))))->toBeTrue()
        ->and(marcaDeHoy()->de(new DateTimeImmutable('2026-10-16 06:00:00', new DateTimeZone('UTC'))))->toBeFalse();
});

test('RNF-09: el número de días viene por parámetro, no es una constante', function () {
    $captura = new DateTimeImmutable('2026-11-07 18:00:00', new DateTimeZone('UTC'));

    expect(marcaDeHoy(7)->de($captura))->toBeTrue()
        ->and(marcaDeHoy(8)->de($captura))->toBeFalse()
        ->and(marcaDeHoy(1)->de(new DateTimeImmutable('2026-11-13 18:00:00', new DateTimeZone('UTC'))))->toBeTrue()
        ->and(marcaDeHoy(1)->de(new DateTimeImmutable('2026-11-14 18:00:00', new DateTimeZone('UTC'))))->toBeFalse();
});

test('RNF-09: un parámetro de días menor que 1 se rechaza', function (int $dias) {
    expect(fn () => marcaDeHoy($dias))->toThrow(ReglaDeNegocioViolada::class, 'dias_de_desactualizacion_invalidos');
})->with([[0], [-5]]);
