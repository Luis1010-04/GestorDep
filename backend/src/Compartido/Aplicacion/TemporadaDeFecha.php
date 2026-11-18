<?php

namespace Src\Compartido\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * La temporada que contiene una fecha, para Alertas (DA-112, DA-117). El adaptador vive en Competencia. Las temporadas no
 * se solapan (arquitectura §7.5), así que hay a lo sumo una.
 */
interface TemporadaDeFecha
{
    /** @return Identificador|null nulo si ninguna temporada no eliminada contiene la fecha */
    public function de(DateTimeImmutable $fecha): ?Identificador;
}
