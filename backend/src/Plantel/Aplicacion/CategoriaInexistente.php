<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La categoría indicada no existe o está eliminada. */
final class CategoriaInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('categoria_inexistente');
    }
}
