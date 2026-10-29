<?php

namespace Src\Competencia\Dominio;

/** Cambio de estado que §3.4 no permite (409, campo `estado`). */
final class TransicionNoPermitida extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('transicion_no_permitida', 'estado');
    }
}
