<?php

namespace Src\Alertas\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Tarjetas de un jugador en un partido, con la temporada del partido (RN-09, DA-91). */
final class TarjetasDePartido
{
    /** @throws ReglaDeNegocioViolada si una cantidad es negativa (`tarjetas_invalidas`) */
    public function __construct(
        private readonly string $temporadaId,
        private readonly int $amarillas,
        private readonly int $rojas,
    ) {
        if ($amarillas < 0 || $rojas < 0) {
            throw new ReglaDeNegocioViolada('tarjetas_invalidas');
        }
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
