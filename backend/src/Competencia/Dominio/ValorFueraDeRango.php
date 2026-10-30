<?php

namespace Src\Competencia\Dominio;

/** Goles fuera de 0 a 99 (§3.4); el campo es el de la petición que lo causó. */
final class ValorFueraDeRango extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('valor_fuera_de_rango', $campo);
    }
}
