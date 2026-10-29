<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Una materia con su calificación ya calculada y el criterio vigente que le toca (RN-03, DD-47). */
final class MateriaEvaluada
{
    public function __construct(
        private readonly ?NumeroDecimal $calificacion,
        private readonly CriterioDeAprobacion $criterio,
    ) {}

    public function calificacion(): ?NumeroDecimal
    {
        return $this->calificacion;
    }

    public function criterio(): CriterioDeAprobacion
    {
        return $this->criterio;
    }

    /** `null` sin calificación: la materia no está aprobada ni reprobada, cuenta como sin calificar. */
    public function aprobada(): ?bool
    {
        return $this->calificacion === null ? null : $this->criterio->aprueba($this->calificacion);
    }
}
