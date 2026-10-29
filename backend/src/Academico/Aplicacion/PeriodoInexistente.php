<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El periodo escolar indicado no existe o está eliminado. */
final class PeriodoInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('periodo_inexistente');
    }
}
