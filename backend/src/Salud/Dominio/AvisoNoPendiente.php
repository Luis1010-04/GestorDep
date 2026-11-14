<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Solo un aviso pendiente se confirma o se descarta (§6.3). */
final class AvisoNoPendiente extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('aviso_no_pendiente');
    }
}
