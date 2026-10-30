<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** El umbral usa una plantilla que no existe o es de otro entrenador, con el mismo cuerpo en los dos casos (v1.0.4 §6.3, 422). */
final class PlantillaInexistente extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('plantilla_inexistente', 'plantilla_id');
    }
}
