<?php

use Src\Academico\Dominio\Asignatura;
use Src\Academico\Dominio\Materia;
use Src\Compartido\Dominio\Identificador;

test('HU-06: una materia expone la asignatura, el periodo y el docente que se le dieron', function () {
    $id = Identificador::generar();
    $asignatura = Identificador::generar();
    $periodo = Identificador::generar();
    $docente = Identificador::generar();
    $instante = new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'));

    $materia = new Materia($id, $asignatura, $periodo, $docente, $instante);

    expect($materia->id())->toBe($id)
        ->and($materia->asignaturaId())->toBe($asignatura)
        ->and($materia->periodoId())->toBe($periodo)
        ->and($materia->docenteId())->toBe($docente)
        ->and($materia->actualizadoEn())->toBe($instante);
});

test('HU-28: una asignatura expone su clave, nombre y grado', function () {
    $id = Identificador::generar();
    $instante = new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'));

    $asignatura = new Asignatura($id, 'MAT-101', 'Matemáticas', 1, $instante);

    expect($asignatura->id())->toBe($id)
        ->and($asignatura->clave())->toBe('MAT-101')
        ->and($asignatura->nombre())->toBe('Matemáticas')
        ->and($asignatura->grado())->toBe(1)
        ->and($asignatura->actualizadoEn())->toBe($instante);
});
