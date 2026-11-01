<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Valor capturado de una métrica; el tipo decide cómo se presenta (entero como número, decimal como cadena, DD-22). */
final class ValorDeMetrica
{
    public function __construct(
        private readonly Identificador $metricaId,
        private readonly NumeroDecimal $valor,
        private readonly TipoDeDato $tipo,
    ) {}

    public function metricaId(): Identificador
    {
        return $this->metricaId;
    }

    public function valor(): NumeroDecimal
    {
        return $this->valor;
    }

    public function tipo(): TipoDeDato
    {
        return $this->tipo;
    }
}
