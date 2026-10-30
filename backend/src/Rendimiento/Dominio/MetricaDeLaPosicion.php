<?php

namespace Src\Rendimiento\Dominio;

/** Una métrica de `metricas_posicion`, con su indicador de obligatoria. */
final class MetricaDeLaPosicion
{
    public function __construct(
        public readonly Metrica $metrica,
        public readonly bool $obligatoria,
    ) {}
}
