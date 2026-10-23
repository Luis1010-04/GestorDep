<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un equipo vigente con ese nombre y categoría. */
final class EquipoDuplicado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('equipo_duplicado');
    }
}
