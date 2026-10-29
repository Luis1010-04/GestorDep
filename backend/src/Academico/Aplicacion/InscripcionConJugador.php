<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Lectura de una inscripción con los datos de expediente del jugador, ya dentro del alcance del docente. */
final class InscripcionConJugador
{
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $materiaId,
        public readonly Identificador $jugadorId,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $matricula,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}
}
