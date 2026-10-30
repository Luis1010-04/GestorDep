<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** El torneo indicado no es de la temporada del partido (§3.4). */
final class TorneoDeOtraTemporada extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('torneo_de_otra_temporada', 'torneo_id');
    }
}
