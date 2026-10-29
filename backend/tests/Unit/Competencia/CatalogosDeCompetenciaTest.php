<?php

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Competencia\Dominio\Lugar;
use Src\Competencia\Dominio\Rival;
use Src\Competencia\Dominio\Temporada;
use Src\Competencia\Dominio\Torneo;

function temporadaDePrueba(string $inicio, string $fin): Temporada
{
    $utc = new DateTimeZone('UTC');

    return new Temporada(Identificador::generar(), 'T1', new DateTimeImmutable($inicio, $utc), new DateTimeImmutable($fin, $utc), new DateTimeImmutable('2026-09-01 15:00:00', $utc));
}

test('HU-28: una temporada con fecha_fin posterior a fecha_inicio expone sus datos', function () {
    $temporada = temporadaDePrueba('2026-08-01', '2026-08-02');

    expect($temporada->nombre())->toBe('T1')
        ->and($temporada->fechaInicio()->format('Y-m-d'))->toBe('2026-08-01')
        ->and($temporada->fechaFin()->format('Y-m-d'))->toBe('2026-08-02')
        ->and($temporada->actualizadoEn()->format('Y-m-d H:i:s'))->toBe('2026-09-01 15:00:00')
        ->and($temporada->id())->toBeInstanceOf(Identificador::class);
});

test('HU-28: una temporada con fecha_fin igual o anterior a fecha_inicio viola rango_de_fechas_invalido', function (string $inicio, string $fin) {
    expect(fn () => temporadaDePrueba($inicio, $fin))->toThrow(ReglaDeNegocioViolada::class, 'rango_de_fechas_invalido');
})->with([
    'mismo día' => ['2026-08-01', '2026-08-01'],
    'un día antes' => ['2026-08-01', '2026-07-31'],
    'mismo día con horas distintas' => ['2026-08-01 08:00:00', '2026-08-01 20:00:00'],
]);

test('HU-28: lugar, rival y torneo exponen sus datos', function () {
    $utc = new DateTimeZone('UTC');
    $instante = new DateTimeImmutable('2026-09-01 15:00:00', $utc);
    $id = Identificador::generar();
    $temporada = Identificador::generar();

    $lugar = new Lugar($id, 'Cancha norte', $instante);
    $rival = new Rival($id, 'Halcones', $instante);
    $torneo = new Torneo($id, 'Copa', $temporada, $instante);

    expect([$lugar->id(), $lugar->nombre(), $lugar->actualizadoEn()])->toBe([$id, 'Cancha norte', $instante])
        ->and([$rival->id(), $rival->nombre(), $rival->actualizadoEn()])->toBe([$id, 'Halcones', $instante])
        ->and([$torneo->id(), $torneo->nombre(), $torneo->temporadaId(), $torneo->actualizadoEn()])->toBe([$id, 'Copa', $temporada, $instante]);
});
