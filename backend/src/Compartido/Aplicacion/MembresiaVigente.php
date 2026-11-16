<?php

namespace Src\Compartido\Aplicacion;

/** Un jugador sin baja y su equipo con membresía vigente; `equipoId` es nulo si no tiene ninguna (DA-115). */
final class MembresiaVigente
{
    public function __construct(
        public readonly string $jugadorId,
        public readonly ?string $equipoId,
    ) {}
}
