<?php

use Src\Academico\Dominio\Inscripcion;
use Src\Compartido\Dominio\Identificador;

test('HU-06: una inscripción expone la materia, el jugador y el instante que se le dieron', function () {
    $id = Identificador::generar();
    $materia = Identificador::generar();
    $jugador = Identificador::generar();
    $instante = new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'));

    $inscripcion = new Inscripcion($id, $materia, $jugador, $instante);

    expect($inscripcion->id())->toBe($id)
        ->and($inscripcion->materiaId())->toBe($materia)
        ->and($inscripcion->jugadorId())->toBe($jugador)
        ->and($inscripcion->actualizadoEn())->toBe($instante);
});
