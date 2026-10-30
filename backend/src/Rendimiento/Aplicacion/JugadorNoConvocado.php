<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** Captura de un jugador visible que no está convocado al partido (§3.3, §6.3, 422). */
final class JugadorNoConvocado extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('jugador_no_convocado', 'jugador_id');
    }
}
