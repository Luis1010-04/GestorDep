<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La temporada indicada no existe o está eliminada. */
final class TemporadaInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('temporada_inexistente');
    }
}
