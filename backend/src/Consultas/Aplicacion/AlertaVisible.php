<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Una alerta abierta que el usuario puede ver (HU-22, DA-89, DA-90). `equipoId` es nulo en la alerta académica. */
final class AlertaVisible
{
    /** @param  array<string, mixed>  $causa  el dato que la activa, nunca un texto libre (DA-105) */
    public function __construct(
        public readonly Identificador $id,
        public readonly string $tipo,
        public readonly Identificador $jugadorId,
        public readonly ?Identificador $equipoId,
        public readonly array $causa,
        public readonly DateTimeImmutable $activadaEn,
    ) {}
}
