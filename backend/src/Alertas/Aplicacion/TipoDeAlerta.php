<?php

namespace Src\Alertas\Aplicacion;

/** Los tres tipos de alerta de la tabla `alertas` (DA-87). */
enum TipoDeAlerta: string
{
    case RiesgoDeBajaAcademica = 'riesgo_baja_academica';
    case RiesgoDeBajaDeportiva = 'riesgo_baja_deportiva';
    case AcumulacionDeSanciones = 'acumulacion_sanciones_deportivas';
}
