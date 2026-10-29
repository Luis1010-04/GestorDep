<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Datos, convocatoria, alineación o resultado de un partido que no está `programado` (409, §3.4). */
final class PartidoNoProgramado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('partido_no_programado');
    }
}
