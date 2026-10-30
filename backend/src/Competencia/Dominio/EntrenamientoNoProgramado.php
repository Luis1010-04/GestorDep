<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Cambio de hora, lugar o responsable de un entrenamiento que ya no está `programado` (409, DA-08). */
final class EntrenamientoNoProgramado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('entrenamiento_no_programado');
    }
}
