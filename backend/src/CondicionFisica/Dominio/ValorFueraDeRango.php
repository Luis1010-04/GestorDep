<?php

namespace Src\CondicionFisica\Dominio;

/** Un valor fuera de los rangos de las tablas de Condición física (DD-62, DA-16). */
final class ValorFueraDeRango extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('valor_fuera_de_rango', $campo);
    }
}
