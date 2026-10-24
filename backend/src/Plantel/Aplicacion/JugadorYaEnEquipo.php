<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El jugador ya tiene una membresía vigente en ese equipo. */
final class JugadorYaEnEquipo extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_ya_en_equipo');
    }
}
