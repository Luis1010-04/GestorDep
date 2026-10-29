<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un rival no eliminado con ese nombre, sin distinguir mayúsculas (HU-28). */
final class RivalDuplicado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('rival_duplicado');
    }
}
