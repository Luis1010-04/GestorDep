<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Ya existe un torneo no eliminado con ese nombre en la temporada, sin distinguir mayúsculas (HU-28). */
final class TorneoDuplicado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('torneo_duplicado');
    }
}
