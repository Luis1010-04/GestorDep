<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Solo quien sostiene el estado vigente lo modifica o da de alta (RN-16, HU-20 c3). */
final class NoEsRegistrante extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('no_es_registrante');
    }
}
