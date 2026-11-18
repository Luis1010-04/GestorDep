<?php

namespace Src\Alertas\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Tarjetas de un jugador en un partido tal como las registró un entrenador del equipo. Dos entrenadores pueden
 * registrar, cada uno, el rendimiento del mismo jugador en el mismo partido (DD-17), así que un partido puede tener
 * varios de estos registros; `TarjetasDePartido::elMayorPorPartido()` los reduce a uno (DA-103).
 */
final class TarjetasRegistradas
{
    /** @throws ReglaDeNegocioViolada si una cantidad es negativa (`tarjetas_invalidas`) */
    public function __construct(
        private readonly string $partidoId,
        private readonly string $temporadaId,
        private readonly int $amarillas,
        private readonly int $rojas,
    ) {
        if ($amarillas < 0 || $rojas < 0) {
            throw new ReglaDeNegocioViolada('tarjetas_invalidas');
        }
    }

    public function partidoId(): string
    {
        return $this->partidoId;
    }

    public function temporadaId(): string
    {
        return $this->temporadaId;
    }

    public function amarillas(): int
    {
        return $this->amarillas;
    }

    public function rojas(): int
    {
        return $this->rojas;
    }
}
