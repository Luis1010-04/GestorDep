<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Competencia\Dominio\CondicionDePartido;
use Src\Competencia\Dominio\TipoDePartido;

/**
 * Lo que pide un `PATCH /partidos/{partido}`. Un campo nulo no cambia, salvo `torneoId` cuando `cambiaTorneo` es
 * verdadero: entonces nulo quita el torneo.
 */
final class CambiosDePartido
{
    public function __construct(
        public readonly ?DateTimeImmutable $iniciaEn = null,
        public readonly ?string $lugarId = null,
        public readonly ?string $rivalId = null,
        public readonly bool $cambiaTorneo = false,
        public readonly ?string $torneoId = null,
        public readonly ?TipoDePartido $tipo = null,
        public readonly ?CondicionDePartido $condicion = null,
        public readonly bool $cancelar = false,
    ) {}

    public function cambiaDatos(): bool
    {
        return $this->iniciaEn !== null || $this->lugarId !== null || $this->rivalId !== null || $this->cambiaTorneo
            || $this->tipo !== null || $this->condicion !== null;
    }
}
