<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe una categoría vigente con ese nombre, sin distinguir mayúsculas (HU-28). */
final class CategoriaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('categoria_duplicada');
    }
}
