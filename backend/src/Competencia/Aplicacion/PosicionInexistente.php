<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Una posición de la alineación no está en el catálogo `posiciones` (§3.4, DA-31). */
final class PosicionInexistente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('posicion_inexistente');
    }
}
