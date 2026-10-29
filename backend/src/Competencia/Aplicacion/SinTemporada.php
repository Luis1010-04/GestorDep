<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** Ninguna temporada no eliminada contiene el día local de `inicia_en` (§3.4, DD-07). */
final class SinTemporada extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('sin_temporada', 'inicia_en');
    }
}
