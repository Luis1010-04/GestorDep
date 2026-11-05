<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Con el estado vigente `apto`, explícito o implícito, no hay nada que cambiar ni dar de alta (§3.4). */
final class SinLesionVigente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('sin_lesion_vigente');
    }
}
