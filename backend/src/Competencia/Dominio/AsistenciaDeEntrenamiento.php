<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Registro de asistencia de un jugador a un entrenamiento realizado (HU-11, RN-11). Inmutable. */
final class AsistenciaDeEntrenamiento
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $entrenamientoId,
        private readonly Identificador $jugadorId,
        private readonly EstadoDeAsistencia $estado,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function conEstado(EstadoDeAsistencia $estado, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->entrenamientoId, $this->jugadorId, $estado, $instante);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function entrenamientoId(): Identificador
    {
        return $this->entrenamientoId;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
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
