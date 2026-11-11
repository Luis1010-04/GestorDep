<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Con el jugador en baja no se registra ni se corrige una prueba física (DD-60, DA-12). Su mensaje es propio (DA-59). */
final class JugadorEnBajaSinPrueba extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
