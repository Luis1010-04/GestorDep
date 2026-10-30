<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Calificación mínima y ventana de partidos de una plantilla o de un umbral (RN-08, HU-15). La calificación va de 1 a
 * 10; la ventana, de 1 a 32 767. Ese máximo es un límite técnico (el de `smallint` en la base), no una regla de negocio
 * (v1.0.4 §6.2, DA-25).
 */
final class CriterioDeEvaluacion
{
    /** Límite técnico de la columna `smallint`, no un parámetro de negocio (RNF-09 no aplica). */
    private const VENTANA_MAXIMA = 32767;

    /** @throws ValorFueraDeRango */
    public function __construct(
        private readonly NumeroDecimal $calificacionMinima,
        private readonly int $ventanaPartidos,
    ) {
        if ($calificacionMinima->menorQue(NumeroDecimal::desdeTexto('1')) || $calificacionMinima->mayorQue(NumeroDecimal::desdeTexto('10'))) {
            throw new ValorFueraDeRango('calificacion_minima');
        }
        if ($ventanaPartidos < 1 || $ventanaPartidos > self::VENTANA_MAXIMA) {
            throw new ValorFueraDeRango('ventana_partidos');
        }
    }

    public function calificacionMinima(): NumeroDecimal
    {
        return $this->calificacionMinima;
    }

    public function ventanaPartidos(): int
    {
        return $this->ventanaPartidos;
    }
}
