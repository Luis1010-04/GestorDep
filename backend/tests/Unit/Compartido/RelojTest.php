<?php

use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojDelSistema;
use Src\Compartido\Infraestructura\RelojFijo;

test('F1-H0-T06: el reloj se puede fijar', function () {
    $reloj = new RelojFijo(new DateTimeImmutable('2026-03-15 10:30:00', new DateTimeZone('UTC')));

    expect($reloj)->toBeInstanceOf(Reloj::class)
        ->and($reloj->ahora()->format('Y-m-d H:i:s'))->toBe('2026-03-15 10:30:00')
        ->and($reloj->ahora())->toEqual($reloj->ahora());
});

test('F1-H0-T06: el reloj fijo devuelve la fecha en UTC aunque se fije en otra zona', function () {
    $reloj = new RelojFijo(new DateTimeImmutable('2026-03-15 04:30:00', new DateTimeZone('America/Mexico_City')));

    expect($reloj->ahora()->getTimezone()->getName())->toBe('UTC')
        ->and($reloj->ahora()->format('Y-m-d H:i:s'))->toBe('2026-03-15 10:30:00');
});

test('F1-H0-T06: el reloj del sistema devuelve la hora actual en UTC', function () {
    $antes = new DateTimeImmutable('now');
    $ahora = (new RelojDelSistema)->ahora();
    $despues = new DateTimeImmutable('now');

    expect(new RelojDelSistema)->toBeInstanceOf(Reloj::class)
        ->and($ahora->getTimezone()->getName())->toBe('UTC')
        ->and($ahora >= $antes && $ahora <= $despues)->toBeTrue();
});
