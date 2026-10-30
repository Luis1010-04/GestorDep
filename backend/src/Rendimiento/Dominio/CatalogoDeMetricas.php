<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Métricas activas de una posición, leídas de `metricas` y `metricas_posicion` (RNF-09). */
interface CatalogoDeMetricas
{
    public function deLaPosicion(Identificador $posicionId): MetricasDeLaPosicion;
}
