<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Lo que produce el cálculo de RN-08: el promedio es nulo con datos insuficientes (§3.3). */
final class EvaluacionCalculada
{
    public function __construct(
        private readonly ResultadoDeRendimiento $resultado,
        private readonly ?NumeroDecimal $promedio,
        private readonly int $partidosConsiderados,
    ) {}

    public function resultado(): ResultadoDeRendimiento
    {
        return $this->resultado;
    }

    public function promedio(): ?NumeroDecimal
    {
        return $this->promedio;
    }

    public function partidosConsiderados(): int
    {
        return $this->partidosConsiderados;
    }
}
