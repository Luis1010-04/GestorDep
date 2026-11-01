<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** La plantilla nombra una posición que no existe o cuyo identificador no es UUID v7 (422; como DA-31 de H3). */
final class PosicionInexistente extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('posicion_inexistente', 'posicion_id');
    }
}
