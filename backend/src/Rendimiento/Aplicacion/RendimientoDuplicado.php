<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** Segunda captura del mismo jugador en el mismo partido por el mismo entrenador (§3.3, §6.3, 409; v1.0.8). */
final class RendimientoDuplicado extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('rendimiento_duplicado', 'jugador_id');
    }
}
