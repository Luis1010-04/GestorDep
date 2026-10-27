<?php

namespace Src\Academico\Dominio;

/** Criterios de aprobación (RN-01, RN-03). */
interface RepositorioDeCriterios
{
    /** El criterio de ámbito general sin fecha de fin; si falta es un fallo de datos de fábrica, no una regla de negocio. */
    public function generalVigente(): CriterioDeAprobacion;
}
