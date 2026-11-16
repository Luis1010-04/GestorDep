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
}
