<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * El jugador no tiene umbral propio del entrenador y su posición no tiene plantilla de fábrica: no hay criterio con qué
 * mostrar el umbral ni calcular el desempeño (409; §6.3 v1.0.6, DA-33).
 */
final class SinUmbralAplicable extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('sin_umbral_aplicable');
    }
}
