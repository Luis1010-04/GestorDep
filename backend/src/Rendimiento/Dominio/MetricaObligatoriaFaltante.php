<?php

namespace Src\Rendimiento\Dominio;

/** Falta una métrica obligatoria de la posición (§3.3, `metricas_posicion.obligatoria`). */
final class MetricaObligatoriaFaltante extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('metrica_obligatoria_faltante', 'metricas');
    }
}
