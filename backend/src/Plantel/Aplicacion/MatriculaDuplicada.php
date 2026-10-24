<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un jugador vigente con esa matrícula. No lleva datos de su expediente (HU-03). */
final class MatriculaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('matricula_duplicada');
    }
}
