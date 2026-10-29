<?php

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\RolDeAlineacion;

test('HU-12 (DA-03): una convocatoria nueva no tiene posición ni rol hasta la alineación', function () {
    $partido = Identificador::generar();
    $jugador = Identificador::generar();
    $instante = new DateTimeImmutable('2026-09-05 15:00:00', new DateTimeZone('UTC'));

    $convocatoria = Convocatoria::nueva($partido, $jugador, $instante);

    expect($convocatoria->partidoId())->toBe($partido)
        ->and($convocatoria->jugadorId())->toBe($jugador)
        ->and($convocatoria->posicionId())->toBeNull()
        ->and($convocatoria->rolAlineacion())->toBeNull()
        ->and($convocatoria->actualizadoEn())->toBe($instante)
        ->and($convocatoria->id()->igualA(Convocatoria::nueva($partido, $jugador, $instante)->id()))->toBeFalse();
});

test('HU-13: el rol de alineación solo es titular o suplente', function () {
    expect(array_map(fn (RolDeAlineacion $rol) => $rol->value, RolDeAlineacion::cases()))->toBe(['titular', 'suplente']);
});
