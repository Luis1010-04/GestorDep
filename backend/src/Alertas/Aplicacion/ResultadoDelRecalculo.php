<?php

namespace Src\Alertas\Aplicacion;

use Throwable;

/** Lo que dejó una corrida de `alertas:recalcular`: cuántos jugadores se recalcularon y qué fallas hubo. */
final class ResultadoDelRecalculo
{
    /** @param  list<Throwable>  $fallas  una por jugador cuyo recálculo falló; los demás se recalcularon */
    public function __construct(
        public readonly int $jugadoresRecalculados,
        public readonly array $fallas,
    ) {}

    public function huboFallas(): bool
    {
        return $this->fallas !== [];
    }
}
