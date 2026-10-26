<?php

use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\Categoria;
use Src\Plantel\Dominio\Equipo;
use Src\Plantel\Dominio\Jugador;

function plantelInstante(string $fecha): DateTimeImmutable
{
    return new DateTimeImmutable($fecha, new DateTimeZone('UTC'));
}

test('HU-28: una categoría expone su identificador, nombre e instante de actualización', function () {
    $id = Identificador::generar();
    $categoria = new Categoria($id, 'Sub-15', plantelInstante('2026-03-10 12:00:00'));

    expect($categoria->id()->igualA($id))->toBeTrue()
        ->and($categoria->nombre())->toBe('Sub-15')
        ->and($categoria->actualizadoEn())->toEqual(plantelInstante('2026-03-10 12:00:00'));
});

test('HU-03: un equipo expone su identificador, nombre, categoría e instante de actualización', function () {
    $id = Identificador::generar();
    $categoriaId = Identificador::generar();
    $equipo = new Equipo($id, 'Águilas', $categoriaId, plantelInstante('2026-03-10 12:00:00'));

    expect($equipo->id()->igualA($id))->toBeTrue()
        ->and($equipo->nombre())->toBe('Águilas')
        ->and($equipo->categoriaId()->igualA($categoriaId))->toBeTrue()
        ->and($equipo->actualizadoEn())->toEqual(plantelInstante('2026-03-10 12:00:00'));
});

test('HU-03: un jugador registrado expone sus datos y nace activo en lo académico y en lo deportivo', function () {
    $id = Identificador::generar();
    $jugador = new Jugador($id, 'Luis', 'Pérez', plantelInstante('2010-05-04 00:00:00'), 'A-001', plantelInstante('2026-03-10 12:00:00'));

    expect($jugador->id()->igualA($id))->toBeTrue()
        ->and($jugador->nombres())->toBe('Luis')
        ->and($jugador->apellidos())->toBe('Pérez')
        ->and($jugador->fechaNacimiento())->toEqual(plantelInstante('2010-05-04 00:00:00'))
        ->and($jugador->matricula())->toBe('A-001')
        ->and($jugador->estatusAcademico())->toBe('activo')
        ->and($jugador->estatusDeportivo())->toBe('activo')
        ->and($jugador->actualizadoEn())->toEqual(plantelInstante('2026-03-10 12:00:00'));
});
