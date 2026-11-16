<?php

namespace Src\Alertas\Dominio;

/**
 * Condición de la alerta de riesgo de baja deportiva (RN-10): el jugador no pasa su umbral de rendimiento o tiene
 * acumulación de sanciones. Los datos insuficientes, por sí solos, no la activan.
 */
final class RiesgoDeBajaDeportiva
{
    public static function seCumple(ResultadoDeEvaluacion $resultado, bool $hayAcumulacionDeSanciones): bool
    {
        return $resultado === ResultadoDeEvaluacion::NoPasa || $hayAcumulacionDeSanciones;
    }
}
