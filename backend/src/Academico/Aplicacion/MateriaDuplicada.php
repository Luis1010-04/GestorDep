<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El docente ya tiene una materia con esa asignatura en ese periodo (HU-06). */
final class MateriaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('materia_duplicada');
    }
}
