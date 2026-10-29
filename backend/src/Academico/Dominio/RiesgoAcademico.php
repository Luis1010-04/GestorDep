<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Nivel de riesgo académico (RN-04): prevalece el más grave que se cumpla. La asistencia no interviene (RN-05). */
final class RiesgoAcademico
{
    /** `null` sin promedio (sin datos). El promedio se redondea a dos decimales antes de comparar. */
    public static function nivel(?NumeroDecimal $promedio, int $materiasReprobadas, ParametrosDeRiesgo $parametros): ?NivelDeRiesgo
    {
        if ($promedio === null) {
            return null;
        }

        $promedio = $promedio->redondear();

        if ($promedio->menorQue($parametros->promedioObservacionMinimo()) || $materiasReprobadas >= $parametros->reprobadasRiesgo()) {
            return NivelDeRiesgo::EnRiesgo;
        }

        if ($promedio->menorQue($parametros->promedioSinRiesgoMinimo()) || $materiasReprobadas >= $parametros->reprobadasObservacion()) {
            return NivelDeRiesgo::EnObservacion;
        }

        return NivelDeRiesgo::SinRiesgo;
    }
}
