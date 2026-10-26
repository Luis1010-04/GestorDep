<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El jugador ya está en baja y no se le puede registrar otra. */
final class JugadorYaEnBaja extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_ya_en_baja');
    }
}
