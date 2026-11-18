<?php

use Src\Alertas\Dominio\AcumulacionDeSanciones;
use Src\Alertas\Dominio\InasistenciaAEntrenamiento;
use Src\Alertas\Dominio\ParametrosDeSanciones;
use Src\Alertas\Dominio\TarjetasDePartido;

/** Conteos de la causa de la acumulación de sanciones (RN-09, DA-105, DA-117). */
test('DA-117: los conteos suman las tarjetas de la temporada y cuentan las faltas injustificadas de la ventana', function () {
    $hoy = new DateTimeImmutable('2026-03-10');
    $tarjetas = [
        new TarjetasDePartido('T-2026', 2, 0),
        new TarjetasDePartido('T-2026', 1, 1),
        new TarjetasDePartido('T-2025', 2, 1),
    ];
    $inasistencias = [
        new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-03-10 23:00'), false),
        new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-02-09 01:00'), false),
        new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-02-08 12:00'), false),
        new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-03-01 12:00'), true),
    ];

    $conteos = AcumulacionDeSanciones::conteos($tarjetas, 'T-2026', $inasistencias, $hoy, new ParametrosDeSanciones(3, 1, 3, 30));

    expect($conteos)->toBe(['tarjetas_amarillas' => 3, 'tarjetas_rojas' => 1, 'faltas_en_ventana' => 2]);
});

test('DA-117 (P-08b): sin temporada los conteos no incluyen tarjetas, pero sí las faltas', function () {
    $conteos = AcumulacionDeSanciones::conteos(
        [new TarjetasDePartido('T-2026', 2, 1)],
        null,
        [new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-03-10'), false)],
        new DateTimeImmutable('2026-03-10'),
        new ParametrosDeSanciones(3, 1, 3, 30),
    );

    expect($conteos)->toBe(['tarjetas_amarillas' => 0, 'tarjetas_rojas' => 0, 'faltas_en_ventana' => 1]);
});

test('DA-117: la ventana de faltas sale del parámetro de días, no del código (RNF-09)', function () {
    $conteos = AcumulacionDeSanciones::conteos(
        [],
        null,
        [new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-03-04'), false), new InasistenciaAEntrenamiento(new DateTimeImmutable('2026-03-03'), false)],
        new DateTimeImmutable('2026-03-10'),
        new ParametrosDeSanciones(3, 1, 3, 7),
    );

    expect($conteos['faltas_en_ventana'])->toBe(1);
});
