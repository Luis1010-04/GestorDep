<?php

use Src\Compartido\Infraestructura\HoyDeNegocioSegunReloj;
use Src\Compartido\Infraestructura\RelojFijo;

function hoyDeNegocioEn(string $instanteUtc, string $zona = 'America/Mexico_City'): DateTimeImmutable
{
    $reloj = new RelojFijo(new DateTimeImmutable($instanteUtc, new DateTimeZone('UTC')));

    return (new HoyDeNegocioSegunReloj($reloj, $zona))->hoy();
}

test('RNF-04: 03:30 UTC sigue siendo el día anterior en America/Mexico_City', function () {
    expect(hoyDeNegocioEn('2026-03-10 03:30:00')->format('Y-m-d'))->toBe('2026-03-09');
});

test('RNF-04: un segundo antes de la medianoche local sigue siendo el día anterior, y a la medianoche cambia', function () {
    expect(hoyDeNegocioEn('2026-03-10 05:59:59')->format('Y-m-d'))->toBe('2026-03-09')
        ->and(hoyDeNegocioEn('2026-03-10 06:00:00')->format('Y-m-d'))->toBe('2026-03-10');
});

test('RNF-04: en una zona adelantada a UTC el día de negocio es el siguiente', function () {
    expect(hoyDeNegocioEn('2026-03-09 20:30:00', 'Asia/Tokyo')->format('Y-m-d'))->toBe('2026-03-10')
        ->and(hoyDeNegocioEn('2026-03-09 14:59:59', 'Asia/Tokyo')->format('Y-m-d'))->toBe('2026-03-09');
});

test('RNF-04: hoy es la medianoche en la zona de la institución', function () {
    $hoy = hoyDeNegocioEn('2026-03-10 18:45:00');

    expect($hoy->format('H:i:s'))->toBe('00:00:00')->and($hoy->getTimezone()->getName())->toBe('America/Mexico_City');
});
