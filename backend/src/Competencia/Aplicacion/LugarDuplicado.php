<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un lugar no eliminado con ese nombre, sin distinguir mayúsculas (HU-28). */
final class LugarDuplicado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('lugar_duplicado');
    }
}
