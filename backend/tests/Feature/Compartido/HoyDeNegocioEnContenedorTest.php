<?php

use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\HoyDeNegocioSegunReloj;
use Src\Compartido\Infraestructura\RelojFijo;

test('RNF-04: el hoy de negocio sale del Reloj del contenedor y de la zona configurada', function () {
    app()->instance(Reloj::class, new RelojFijo(new DateTimeImmutable('2026-03-10 03:30:00', new DateTimeZone('UTC'))));

    expect(app(HoyDeNegocio::class))->toBeInstanceOf(HoyDeNegocioSegunReloj::class)
        ->and(config('app.zona_presentacion'))->toBe('America/Mexico_City')
        ->and(app(HoyDeNegocio::class)->hoy()->format('Y-m-d'))->toBe('2026-03-09');

    config(['app.zona_presentacion' => 'Asia/Tokyo']);
    expect(app(HoyDeNegocio::class)->hoy()->format('Y-m-d'))->toBe('2026-03-10');
});
