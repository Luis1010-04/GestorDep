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
        $conteos = self::conteos($tarjetas, $temporadaId, $inasistencias, $fechaDeCalculo, $parametros);

        return $conteos['tarjetas_rojas'] >= $parametros->tarjetasRojas()
            || $conteos['tarjetas_amarillas'] >= $parametros->tarjetasAmarillas()
            || $conteos['faltas_en_ventana'] >= $parametros->faltasUmbral();
    }

    /**
     * Lo que cuenta la regla, que es también la causa de la alerta (DA-105, DA-117): las tarjetas de la temporada y las
     * faltas injustificadas de la ventana móvil.
     *
     * @param  list<TarjetasDePartido>  $tarjetas
     * @param  list<InasistenciaAEntrenamiento>  $inasistencias
     * @return array{tarjetas_amarillas: int, tarjetas_rojas: int, faltas_en_ventana: int}
     */
    public static function conteos(
        array $tarjetas,
        ?string $temporadaId,
        array $inasistencias,
        DateTimeImmutable $fechaDeCalculo,
        ParametrosDeSanciones $parametros,
    ): array {
        $amarillas = 0;
        $rojas = 0;

        foreach ($tarjetas as $deUnPartido) {
            if ($deUnPartido->temporadaId() === $temporadaId) {
                $amarillas += $deUnPartido->amarillas();
                $rojas += $deUnPartido->rojas();
            }
        }

        return [
            'tarjetas_amarillas' => $amarillas,
            'tarjetas_rojas' => $rojas,
            'faltas_en_ventana' => VentanaDeFaltas::contar($inasistencias, $fechaDeCalculo, $parametros->faltasVentanaDias()),
        ];
    }
}
