<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La baja detiene la captura académica: ni calificaciones ni asistencias de un jugador en baja (DD-52). Mismo código que H1. */
final class JugadorEnBajaSinCaptura extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
