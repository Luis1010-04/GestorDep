<?php

namespace Src\Alertas\Dominio;

use DateTimeImmutable;

/**
 * Condición de la alerta de acumulación de sanciones deportivas (RN-09), por jugador y equipo: las tarjetas rojas o
 * las amarillas de la temporada alcanzan su umbral, o las faltas injustificadas de la ventana alcanzan el suyo. Las
 * tarjetas de otra temporada no cuentan, así que una roja deja de contar al terminar la temporada en que ocurrió.
 */
final class AcumulacionDeSanciones
{
    /**
     * @param  list<TarjetasDePartido>  $tarjetas
     * @param  string|null  $temporadaId  la temporada que contiene la fecha de cálculo; nulo si ninguna la contiene: entonces no se
     *                                    cuentan tarjetas y las faltas sí (P-08b)
     * @param  list<InasistenciaAEntrenamiento>  $inasistencias
     */
    public static function seCumple(
        array $tarjetas,
        ?string $temporadaId,
        array $inasistencias,
        DateTimeImmutable $fechaDeCalculo,
        ParametrosDeSanciones $parametros,
    ): bool {
        $amarillas = 0;
        $rojas = 0;

        foreach ($tarjetas as $deUnPartido) {
            if ($deUnPartido->temporadaId() === $temporadaId) {
                $amarillas += $deUnPartido->amarillas();
                $rojas += $deUnPartido->rojas();
            }
        }

        return $rojas >= $parametros->tarjetasRojas()
            || $amarillas >= $parametros->tarjetasAmarillas()
            || VentanaDeFaltas::contar($inasistencias, $fechaDeCalculo, $parametros->faltasVentanaDias()) >= $parametros->faltasUmbral();
    }
}
