<?php

namespace Src\Salud\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\EstadoOficial;
use Src\Salud\Dominio\TipoDeEstado;

/** Lo que un usuario ve del estado oficial: sin tipo, zona ni causa, y `lesionId` solo para el registrante (RN-15, DA-18). */
final class EstadoVisible
{
    private function __construct(
        public readonly string $estado,
        public readonly ?string $fechaEstimadaRegreso,
        public readonly ?DateTimeImmutable $vigenteDesde,
        public readonly bool $esImplicito,
        public readonly bool $requiereActualizacion,
        public readonly ?string $lesionId,
    ) {}

    /** Sin ningún registro, el jugador está apto (DA-01). */
    public static function aptoImplicito(): self
    {
        return new self(TipoDeEstado::Apto->value, null, null, true, false, null);
    }

    public static function para(EstadoOficial $estado, Identificador $lector, string $hoy): self
    {
        return new self(
            $estado->tipo()->value,
            $estado->fechaEstimadaRegreso(),
            $estado->vigenteDesde(),
            false,
            $estado->requiereActualizacion($hoy),
            $estado->esSostenidoPor($lector) ? $estado->lesionId()?->valor() : null,
        );
    }
}
