<?php

namespace Src\Compartido\Dominio\Eventos;

/** Se registró o corrigió una calificación (HU-07). Solo identificadores (arquitectura §3). */
final class CalificacionRegistrada
{
    public function __construct(
        public readonly string $inscripcionId,
        public readonly string $materiaId,
        public readonly string $jugadorId,
    ) {}
}
