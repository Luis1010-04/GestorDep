<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Criterios de aprobación (RN-01, RN-03). */
interface RepositorioDeCriterios
{
    /** El criterio de ámbito general sin fecha de fin; si falta es un fallo de datos de fábrica, no una regla de negocio. */
    public function generalVigente(): CriterioDeAprobacion;

    /**
     * El criterio propio vigente de cada materia que lo tiene (RN-03).
     *
     * @param  list<Identificador>  $materias
     * @return array<string, CriterioDeAprobacion> por identificador de materia; las que no tienen criterio propio no aparecen
     */
    public function propiosVigentes(array $materias): array;

    public function propioVigente(Identificador $materia): ?CriterioVigente;

    /**
     * Cierra el criterio propio vigente de la materia (su `vigente_hasta` pasa a `$hoy`) y crea el nuevo desde `$hoy`, con
     * sus historiales, en una sola transacción: si algo falla no queda ni el cierre ni el nuevo.
     */
    public function reemplazarPropio(Identificador $materia, NumeroDecimal $minimo, DateTimeImmutable $hoy): CriterioVigente;
}
