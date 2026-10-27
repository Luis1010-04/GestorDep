<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Materia (HU-06): una asignatura impartida por un docente en un periodo escolar. Solo se referencian por identificador. */
final class Materia
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $asignaturaId,
        private readonly Identificador $periodoId,
        private readonly Identificador $docenteId,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function asignaturaId(): Identificador
    {
        return $this->asignaturaId;
    }

    public function periodoId(): Identificador
    {
        return $this->periodoId;
    }

    public function docenteId(): Identificador
    {
        return $this->docenteId;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
