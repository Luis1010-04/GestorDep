<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** Segunda captura del mismo jugador en el mismo partido (§3.3, §6.3, 409). */
final class RendimientoDuplicado extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('rendimiento_duplicado', 'jugador_id');
    }
}
