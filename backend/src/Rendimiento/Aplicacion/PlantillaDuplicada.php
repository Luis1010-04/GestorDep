<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** Plantilla propia con el nombre de otra propia del mismo entrenador o de una de fábrica en la misma posición (v1.0.4 §6.3, 409). */
final class PlantillaDuplicada extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('plantilla_duplicada', 'nombre');
    }
}
