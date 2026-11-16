<?php

namespace Src\Alertas\Dominio;

use DateTimeImmutable;

/**
 * Ventana móvil de faltas de RN-09 (DD-19): los días naturales que terminan en la fecha de cálculo, inclusive. Con
 * 30 días va de `fecha de cálculo − 29 días` a la fecha de cálculo. Solo cuentan las faltas injustificadas.
 */
final class VentanaDeFaltas
{
    /** @param  list<InasistenciaAEntrenamiento>  $inasistencias */
    public static function contar(array $inasistencias, DateTimeImmutable $fechaDeCalculo, int $dias): int
    {
        $ultimoDia = $fechaDeCalculo->format('Y-m-d');
        $primerDia = $fechaDeCalculo->modify('-'.($dias - 1).' days')->format('Y-m-d');
        $faltas = 0;

        foreach ($inasistencias as $inasistencia) {
            $dia = $inasistencia->fecha()->format('Y-m-d');

            if (! $inasistencia->justificada() && $dia >= $primerDia && $dia <= $ultimoDia) {
                $faltas++;
            }
        }

        return $faltas;
    }
}
