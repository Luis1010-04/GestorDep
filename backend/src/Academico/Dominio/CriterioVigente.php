<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;

/** Un criterio de aprobación y el día desde el que rige (DD-47). */
final class CriterioVigente
{
    public function __construct(
        private readonly CriterioDeAprobacion $criterio,
        private readonly DateTimeImmutable $vigenteDesde,
    ) {}

    public function criterio(): CriterioDeAprobacion
    {
        return $this->criterio;
    }

    public function vigenteDesde(): DateTimeImmutable
    {
        return $this->vigenteDesde;
    }
}
