<?php

use Src\Compartido\Dominio\Instante;

test('F1-H1-FIX: el instante se trunca a segundos', function () {
    $instante = Instante::truncadoASegundos(new DateTimeImmutable('2026-03-15 12:00:00.900123', new DateTimeZone('UTC')));

    expect($instante->format('Y-m-d H:i:s.u'))->toBe('2026-03-15 12:00:00.000000');
});

test('F1-H1-FIX: el instante en otra zona horaria queda en UTC y truncado', function () {
    $instante = Instante::truncadoASegundos(new DateTimeImmutable('2026-03-15 06:00:00.900', new DateTimeZone('America/Mexico_City')));

    expect($instante->getTimezone()->getName())->toBe('UTC')
        ->and($instante->format('Y-m-d H:i:s.u'))->toBe('2026-03-15 12:00:00.000000');
});
