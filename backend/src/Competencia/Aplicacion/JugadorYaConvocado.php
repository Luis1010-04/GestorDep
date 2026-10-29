<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** El jugador ya tiene una convocatoria no eliminada en el partido (409, campo `jugador_id`, §3.4). */
final class JugadorYaConvocado extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('jugador_ya_convocado', 'jugador_id');
    }
}
