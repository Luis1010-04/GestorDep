<?php

namespace Src\Alertas\Dominio;

/**
 * Condición de la alerta de riesgo de baja académica (RN-21). El nivel de riesgo llega como valor primitivo desde
 * Académico; el nivel que la activa viene de `parametros_institucionales` (`alerta_academica`, DA-100), no del código.
 */
final class AlertaAcademica
{
    /** @param  string|null  $nivelDeRiesgo  nulo cuando el jugador no tiene datos para calcular el riesgo */
    public static function seCumple(?string $nivelDeRiesgo, string $nivelQueActiva): bool
    {
        return $nivelDeRiesgo === $nivelQueActiva;
    }
}
