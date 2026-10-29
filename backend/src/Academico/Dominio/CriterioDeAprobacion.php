<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Calificación mínima para aprobar una materia (RN-01) y su ámbito (RN-03). El valor llega de la base: el dominio no
 * conoce ningún mínimo.
 */
final class CriterioDeAprobacion
{
    public function __construct(
        private readonly AmbitoDeCriterio $ambito,
        private readonly NumeroDecimal $calificacionMinima,
    ) {}

    /**
     * El criterio propio de la materia prevalece sobre el general; el resultado indica cuál se aplicó con `aplicaA()`.
     *
     * @throws ReglaDeNegocioViolada si el propio no es de ámbito materia o el general no es de ámbito general
     */
    public static function resolver(?self $propio, self $general): self
    {
        if ($general->ambito !== AmbitoDeCriterio::General || ($propio !== null && $propio->ambito !== AmbitoDeCriterio::Materia)) {
            throw new ReglaDeNegocioViolada('criterio_de_ambito_invalido');
        }

        return $propio ?? $general;
    }

    public function aplicaA(): AmbitoDeCriterio
    {
        return $this->ambito;
    }

    public function calificacionMinima(): NumeroDecimal
    {
        return $this->calificacionMinima;
    }

    /** Una calificación igual al mínimo aprueba. */
    public function aprueba(NumeroDecimal $calificacion): bool
    {
        return $calificacion->mayorOIgualQue($this->calificacionMinima);
    }
}
