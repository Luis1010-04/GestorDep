<?php

namespace Src\Academico\Dominio;

/** Nivel de riesgo académico (RN-04). La ausencia de datos no es un nivel: se expresa con `null`. */
enum NivelDeRiesgo: string
{
    case SinRiesgo = 'sin_riesgo';
    case EnObservacion = 'en_observacion';
    case EnRiesgo = 'en_riesgo';
}
