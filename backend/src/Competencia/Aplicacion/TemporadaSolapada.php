<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Las fechas se cruzan con las de otra temporada no eliminada (DD-07). */
final class TemporadaSolapada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('temporada_solapada');
    }
}
