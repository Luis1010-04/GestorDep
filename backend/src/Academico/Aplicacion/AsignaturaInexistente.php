<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La asignatura indicada no existe o está eliminada. */
final class AsignaturaInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('asignatura_inexistente');
    }
}
