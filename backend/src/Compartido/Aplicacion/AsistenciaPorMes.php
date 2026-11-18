<?php

namespace Src\Compartido\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Porcentaje de asistencia de cada mes, con la fecha de la última actualización de los registros que lo forman (HU-25,
 * RNF-04). Lo devuelven los puertos de asistencia a clases y a entrenamientos (DA-109).
 */
final class AsistenciaPorMes
{
    /**
     * @param  array<string, NumeroDecimal>  $porcentajes  por mes `AAAA-MM`, del más antiguo al más reciente; un mes sin registros no aparece
     * @param  DateTimeImmutable|null  $actualizadoEn  en UTC; nulo si no hay ningún registro
     */
    public function __construct(
        public readonly array $porcentajes,
        public readonly ?DateTimeImmutable $actualizadoEn,
    ) {}
}
