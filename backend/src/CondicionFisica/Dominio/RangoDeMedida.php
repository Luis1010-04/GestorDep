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

    /**
     * El rango que de verdad se puede guardar: el máximo efectivo es el menor entre el del rango y el de la columna (AU-09).
     * Si el rango ya cabe, devuelve el mismo.
     */
    public function acotadoA(NumeroDecimal $maximoDeColumna): self
    {
        return $this->maximo->mayorQue($maximoDeColumna) ? new self($this->minimo, $maximoDeColumna) : $this;
    }

    /** @throws ValorFueraDeRango */
    public function exigir(NumeroDecimal $valor, string $campo): void
    {
        if ($valor->menorQue($this->minimo) || $valor->mayorQue($this->maximo)) {
            throw new ValorFueraDeRango($campo);
        }
    }
}
