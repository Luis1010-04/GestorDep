<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Inscripción de un jugador en una materia (HU-06). Otorga al docente el alcance sobre el jugador (RN-20). */
final class Inscripcion
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $materiaId,
        private readonly Identificador $jugadorId,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function materiaId(): Identificador
    {
        return $this->materiaId;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
