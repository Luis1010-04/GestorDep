<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** Un partido oficial o de torneo debe indicar el torneo (§3.4). */
final class TorneoObligatorio extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('torneo_obligatorio', 'torneo_id');
    }
}
