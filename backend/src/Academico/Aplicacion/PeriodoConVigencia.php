<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\PeriodoEscolar;

/** Un periodo y si es el vigente según DD-45. */
final class PeriodoConVigencia
{
    public function __construct(
        public readonly PeriodoEscolar $periodo,
        public readonly bool $vigente,
    ) {}
}
