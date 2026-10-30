<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El lugar indicado no existe o está eliminado (DA-11). */
final class LugarInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('lugar_inexistente');
    }
}
