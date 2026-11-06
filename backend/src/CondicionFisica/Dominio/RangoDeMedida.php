<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Mínimo y máximo de una medida, los dos válidos. Salen de una tabla, nunca del código (RNF-09, DA-16). */
final class RangoDeMedida
{
    public function __construct(
        private readonly NumeroDecimal $minimo,
        private readonly NumeroDecimal $maximo,
    ) {}

    /** @throws ValorFueraDeRango */
    public function exigir(NumeroDecimal $valor, string $campo): void
    {
        if ($valor->menorQue($this->minimo) || $valor->mayorQue($this->maximo)) {
            throw new ValorFueraDeRango($campo);
        }
    }
}
