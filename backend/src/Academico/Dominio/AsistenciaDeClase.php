<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Asistencia de una inscripción a una clase en una fecha (HU-07 criterio 3). Una por inscripción y fecha. */
final class AsistenciaDeClase
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $inscripcionId,
        private readonly DateTimeImmutable $fecha,
        private readonly EstadoDeAsistencia $estado,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function inscripcionId(): Identificador
    {
        return $this->inscripcionId;
    }

    public function fecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function estado(): EstadoDeAsistencia
    {
        return $this->estado;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
