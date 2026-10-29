<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El rival indicado no existe o está eliminado (DA-24). */
final class RivalInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('rival_inexistente');
    }
}
