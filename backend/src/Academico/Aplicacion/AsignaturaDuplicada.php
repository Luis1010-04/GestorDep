<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe una asignatura vigente con esa clave, sin distinguir mayúsculas (HU-28). */
final class AsignaturaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('asignatura_duplicada');
    }
}
