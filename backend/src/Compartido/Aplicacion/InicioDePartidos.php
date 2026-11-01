<?php

namespace Src\Compartido\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Fecha de inicio de partidos, para contextos que no pueden leer las tablas de Competencia (docs/hitos/f1-h4.md §8,
 * T04, v1.0.5). Rendimiento la usa para ordenar las últimas N calificaciones (RN-08). El adaptador vive en Competencia.
 */
interface InicioDePartidos
{
    /**
     * Los partidos que no existen o están eliminados no aparecen.
     *
     * @param  list<Identificador>  $partidos
     * @return array<string, DateTimeImmutable> por identificador del partido, en UTC
     */
    public function de(array $partidos): array;
}
