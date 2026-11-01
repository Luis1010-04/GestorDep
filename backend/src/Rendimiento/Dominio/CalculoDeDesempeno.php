<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Pasa, no pasa o datos insuficientes (RN-08, HU-16; docs/hitos/f1-h4.md §3.3, v1.0.5). Con menos de N calificaciones
 * (N es la ventana) el resultado es datos insuficientes, sin promedio. Si no, el promedio de las N más recientes se
 * redondea a dos decimales (HALF_UP) y ese promedio redondeado, el mismo que se guarda y se muestra, se compara con la
 * calificación mínima: pasa si es mayor o igual.
 */
final class CalculoDeDesempeno
{
    /** @param  list<int>  $calificaciones  de la más reciente a la más antigua */
    public static function evaluar(CriterioDeEvaluacion $criterio, array $calificaciones): EvaluacionCalculada
    {
        $ventana = $criterio->ventanaPartidos();
        if (count($calificaciones) < $ventana) {
            return new EvaluacionCalculada(ResultadoDeRendimiento::DatosInsuficientes, null, count($calificaciones));
        }

        $promedio = NumeroDecimal::media(
            array_map(fn (int $calificacion) => NumeroDecimal::desdeTexto((string) $calificacion), array_slice($calificaciones, 0, $ventana)),
            2,
        );
        $resultado = $promedio->mayorOIgualQue($criterio->calificacionMinima()) ? ResultadoDeRendimiento::Pasa : ResultadoDeRendimiento::NoPasa;

        return new EvaluacionCalculada($resultado, $promedio, $ventana);
    }
}
