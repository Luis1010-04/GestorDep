<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** El umbral usa una plantilla de otra posición que la del jugador en el equipo (§6.3, 422). */
final class PlantillaDeOtraPosicion extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('plantilla_de_otra_posicion', 'plantilla_id');
    }
}
