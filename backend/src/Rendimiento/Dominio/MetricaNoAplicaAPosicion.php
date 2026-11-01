<?php

namespace Src\Rendimiento\Dominio;

/** La métrica no es de la posición del jugador en el equipo (§3.3); el campo es `metricas.N.metrica_id`. */
final class MetricaNoAplicaAPosicion extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('metrica_no_aplica_a_posicion', $campo);
    }
}
