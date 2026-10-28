<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El jugador ya está inscrito en esa materia (HU-06). */
final class JugadorYaInscrito extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_ya_inscrito');
    }
}
