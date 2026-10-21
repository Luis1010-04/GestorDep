<?php

use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\RelojDelSistema;
use Src\Compartido\Infraestructura\RelojFijo;

test('F1-H0-T06: la aplicacion resuelve el reloj con el reloj del sistema', function () {
    expect(app(Reloj::class))->toBeInstanceOf(RelojDelSistema::class);
});

test('F1-H0-T06: una prueba puede fijar el reloj de la aplicacion', function () {
    $momento = new DateTimeImmutable('2026-03-15 10:30:00', new DateTimeZone('UTC'));
    app()->instance(Reloj::class, new RelojFijo($momento));

    expect(app(Reloj::class)->ahora())->toEqual($momento);
});
