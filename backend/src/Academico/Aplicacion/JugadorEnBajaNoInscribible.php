<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Un jugador en baja no se puede inscribir (DD-52). Mismo código y cuerpo que la asignación a un equipo de H1. */
final class JugadorEnBajaNoInscribible extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
