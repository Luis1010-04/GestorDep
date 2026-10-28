<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe una asistencia de la inscripción en esa fecha (§6.3). */
final class AsistenciaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('asistencia_duplicada');
    }
}
