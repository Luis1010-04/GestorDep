<?php

namespace Src\Compartido\Aplicacion;

/** La última evaluación de rendimiento de un entrenador sobre un jugador en un equipo (DA-92). El promedio es un decimal en texto. */
final class EvaluacionVigente
{
    public function __construct(
        public readonly string $entrenadorId,
        public readonly string $resultado,
        public readonly ?string $promedio,
    ) {}
}
