<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** El responsable no es un entrenador con asignación vigente al equipo (§3.4, DA-13). */
final class ResponsableNoAsignado extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('responsable_no_asignado', 'responsable_id');
    }
}
