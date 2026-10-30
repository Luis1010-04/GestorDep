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

test('HU-13: conAlineacion fija o quita la posición y el rol, y tieneAlineacion compara los dos', function () {
    $utc = new DateTimeZone('UTC');
    $convocatoria = Convocatoria::nueva(Identificador::generar(), Identificador::generar(), new DateTimeImmutable('2026-09-05 15:00:00', $utc));
    $defensa = Identificador::generar();
    $instante = new DateTimeImmutable('2026-09-13 01:00:00', $utc);

    $alineada = $convocatoria->conAlineacion($defensa, RolDeAlineacion::Titular, $instante);
    $sinAlineacion = $alineada->conAlineacion(null, null, $instante);

    expect($alineada->posicionId())->toBe($defensa)
        ->and($alineada->rolAlineacion())->toBe(RolDeAlineacion::Titular)
        ->and($alineada->actualizadoEn())->toBe($instante)
        ->and($alineada->id())->toBe($convocatoria->id())
        ->and($alineada->partidoId())->toBe($convocatoria->partidoId())
        ->and($alineada->jugadorId())->toBe($convocatoria->jugadorId())
        ->and($sinAlineacion->posicionId())->toBeNull()
        ->and($sinAlineacion->rolAlineacion())->toBeNull()
        ->and($alineada->tieneAlineacion(Identificador::desdeTexto($defensa->valor()), RolDeAlineacion::Titular))->toBeTrue()
        ->and($alineada->tieneAlineacion($defensa, RolDeAlineacion::Suplente))->toBeFalse()
        ->and($alineada->tieneAlineacion(Identificador::generar(), RolDeAlineacion::Titular))->toBeFalse()
        ->and($alineada->tieneAlineacion(null, RolDeAlineacion::Titular))->toBeFalse()
        ->and($alineada->tieneAlineacion(null, null))->toBeFalse()
        ->and($convocatoria->tieneAlineacion(null, null))->toBeTrue()
        ->and($convocatoria->tieneAlineacion($defensa, null))->toBeFalse()
        ->and($convocatoria->tieneAlineacion(null, RolDeAlineacion::Suplente))->toBeFalse();
});
