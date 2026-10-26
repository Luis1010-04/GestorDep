<?php

namespace Src\Plantel\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Baja registrada de un jugador (RN-23). */
final class Baja
{
    public function __construct(
        public readonly Identificador $jugadorId,
        public readonly string $motivo,
        public readonly DateTimeImmutable $fecha,
        public readonly DateTimeImmutable $creadoEn,
    ) {}
}
