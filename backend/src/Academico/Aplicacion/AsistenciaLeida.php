<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Compartido\Dominio\Identificador;

/** Lectura de una asistencia a clase. */
final class AsistenciaLeida
{
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $inscripcionId,
        public readonly DateTimeImmutable $fecha,
        public readonly EstadoDeAsistencia $estado,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}
}
