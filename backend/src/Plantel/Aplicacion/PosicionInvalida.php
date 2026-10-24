<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El código de posición no existe. */
final class PosicionInvalida extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('posicion_invalida');
    }
}
