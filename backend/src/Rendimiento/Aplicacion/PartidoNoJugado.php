<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Captura de rendimiento de un partido que no está `jugado` (§6.3, 409). */
final class PartidoNoJugado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('partido_no_jugado');
    }
}
