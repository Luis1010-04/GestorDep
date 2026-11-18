<?php

namespace Src\Alertas\Dominio;

/**
 * Resultado de la evaluación de rendimiento (RN-08) tal como lo recibe Alertas. Es un valor propio de este contexto:
 * Alertas no importa el dominio de Rendimiento.
 */
enum ResultadoDeEvaluacion: string
{
    case Pasa = 'pasa';
    case NoPasa = 'no_pasa';
    case DatosInsuficientes = 'datos_insuficientes';

    /**
     * Resultado de un jugador en un equipo a partir de la evaluación vigente de cada entrenador del equipo (DA-102):
     * basta que una sea `no_pasa`. Si ninguna lo es, es `pasa` cuando alguna pasa. Sin ninguna evaluación, o con todas
     * en datos insuficientes, es `datos_insuficientes` (RN-08, DA-107).
     *
     * @param  list<self>  $porEntrenador
     */
    public static function delEquipo(array $porEntrenador): self
    {
        if (in_array(self::NoPasa, $porEntrenador, true)) {
            return self::NoPasa;
        }

        return in_array(self::Pasa, $porEntrenador, true) ? self::Pasa : self::DatosInsuficientes;
    }
}
