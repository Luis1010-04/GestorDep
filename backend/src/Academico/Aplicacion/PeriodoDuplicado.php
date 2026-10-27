<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un periodo vigente con ese nombre, sin distinguir mayúsculas (HU-28). */
final class PeriodoDuplicado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('periodo_duplicado');
    }
}
