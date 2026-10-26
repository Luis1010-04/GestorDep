<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Dar de baja a un jugador exige un motivo (RN-23). */
final class MotivoObligatorio extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('motivo_obligatorio');
    }
}
