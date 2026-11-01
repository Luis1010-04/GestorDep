<?php

namespace Src\Rendimiento\Dominio;

/** Minutos, tarjetas, calificación, observación o valor de métrica fuera de rango (§3.3, §6.3); el campo es el de la petición. */
final class ValorFueraDeRango extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('valor_fuera_de_rango', $campo);
    }
}
