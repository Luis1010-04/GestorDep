<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe una calificación del mismo tipo y número de parcial en la inscripción (§6.3). */
final class CalificacionDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('calificacion_duplicada');
    }
}
