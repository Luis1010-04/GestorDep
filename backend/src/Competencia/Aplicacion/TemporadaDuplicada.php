<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe una temporada no eliminada con ese nombre, sin distinguir mayúsculas (HU-28). */
final class TemporadaDuplicada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('temporada_duplicada');
    }
}
