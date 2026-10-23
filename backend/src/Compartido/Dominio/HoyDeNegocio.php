<?php

namespace Src\Compartido\Dominio;

use DateTimeImmutable;

/** El "hoy" de la institución: el día calendario vigente en su zona horaria, no en UTC (RNF-04). */
interface HoyDeNegocio
{
    /** Medianoche del día de hoy en la zona de la institución; formatéese con `Y-m-d` para una columna `date`. */
    public function hoy(): DateTimeImmutable;
}
