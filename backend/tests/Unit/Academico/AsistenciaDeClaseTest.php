<?php

use Src\Academico\Dominio\AsistenciaDeClase;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Compartido\Dominio\Identificador;

test('HU-07: una asistencia a clase expone lo que se le dio', function () {
    $id = Identificador::generar();
    $inscripcion = Identificador::generar();
    $fecha = new DateTimeImmutable('2026-03-09', new DateTimeZone('UTC'));
    $instante = new DateTimeImmutable('2026-03-10 12:00:00', new DateTimeZone('UTC'));

    $asistencia = new AsistenciaDeClase($id, $inscripcion, $fecha, EstadoDeAsistencia::FaltaJustificada, $instante);

    expect($asistencia->id())->toBe($id)
        ->and($asistencia->inscripcionId())->toBe($inscripcion)
        ->and($asistencia->fecha())->toBe($fecha)
        ->and($asistencia->estado())->toBe(EstadoDeAsistencia::FaltaJustificada)
        ->and($asistencia->actualizadoEn())->toBe($instante);
});

test('RN-05: los estados de asistencia son presente, falta y falta justificada', function () {
    expect(array_map(fn (EstadoDeAsistencia $estado): string => $estado->value, EstadoDeAsistencia::cases()))->toBe(['presente', 'falta', 'falta_justificada']);
});
