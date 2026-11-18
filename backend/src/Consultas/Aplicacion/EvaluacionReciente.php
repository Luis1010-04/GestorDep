<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\NumeroDecimal;

/** La última evaluación del consultante para el jugador en el equipo, ya guardada en `evaluaciones_rendimiento` (RN-08). */
final class EvaluacionReciente
{
    public function __construct(
        public readonly ?NumeroDecimal $promedio,
        public readonly string $resultado,
        public readonly DateTimeImmutable $calculadoEn,
    ) {}
}
