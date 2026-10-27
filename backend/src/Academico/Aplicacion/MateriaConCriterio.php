<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CriterioDeAprobacion;

/** Una materia y el criterio de aprobación que le aplica hoy (RN-03). */
final class MateriaConCriterio
{
    public function __construct(
        public readonly MateriaConDetalle $materia,
        public readonly CriterioDeAprobacion $criterio,
    ) {}
}
