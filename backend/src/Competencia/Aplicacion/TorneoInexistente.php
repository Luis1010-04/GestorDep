<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El torneo indicado no existe o está eliminado (DA-24). */
final class TorneoInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('torneo_inexistente');
    }
}
