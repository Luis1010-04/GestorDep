<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Umbrales del riesgo académico (RN-04). Los arma la capa de aplicación desde `parametros_institucionales`; el dominio
 * solo valida que sean coherentes, porque un parámetro mal cargado cambiaría el riesgo de todos los jugadores (DA-08).
 */
final class ParametrosDeRiesgo
{
    private const CALIFICACION_MINIMA_DE_ESCALA = '0';

    private const CALIFICACION_MAXIMA_DE_ESCALA = '10';

    /** @throws ReglaDeNegocioViolada si los umbrales son incoherentes (`parametros_de_riesgo_invalidos`) */
    public function __construct(
        private readonly NumeroDecimal $promedioSinRiesgoMinimo,
        private readonly NumeroDecimal $promedioObservacionMinimo,
        private readonly int $reprobadasObservacion,
        private readonly int $reprobadasRiesgo,
    ) {
        $cero = NumeroDecimal::desdeTexto(self::CALIFICACION_MINIMA_DE_ESCALA);
        $diez = NumeroDecimal::desdeTexto(self::CALIFICACION_MAXIMA_DE_ESCALA);

        if (
            $promedioObservacionMinimo->menorQue($cero)
            || $promedioSinRiesgoMinimo->mayorQue($diez)
            || $promedioObservacionMinimo->mayorQue($promedioSinRiesgoMinimo)
            || $reprobadasObservacion < 1
            || $reprobadasRiesgo < $reprobadasObservacion
        ) {
            throw new ReglaDeNegocioViolada('parametros_de_riesgo_invalidos');
        }
    }

    public function promedioSinRiesgoMinimo(): NumeroDecimal
    {
        return $this->promedioSinRiesgoMinimo;
    }

    public function promedioObservacionMinimo(): NumeroDecimal
    {
        return $this->promedioObservacionMinimo;
    }

    public function reprobadasObservacion(): int
    {
        return $this->reprobadasObservacion;
    }

    public function reprobadasRiesgo(): int
    {
        return $this->reprobadasRiesgo;
    }
}
