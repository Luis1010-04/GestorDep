<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Un jugador en baja no se puede asignar a un equipo (DD-30). */
final class JugadorEnBaja extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
