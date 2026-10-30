<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La asistencia solo se captura en un entrenamiento `realizado` (§3.4). */
final class EntrenamientoNoRealizado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('entrenamiento_no_realizado');
    }
}
