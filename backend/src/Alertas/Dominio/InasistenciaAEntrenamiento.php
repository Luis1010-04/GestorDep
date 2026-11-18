<?php

namespace Src\Alertas\Dominio;

use DateTimeImmutable;

/** Inasistencia de un jugador a un entrenamiento realizado: falta o falta justificada (RN-09). */
final class InasistenciaAEntrenamiento
{
    public function __construct(
        private readonly DateTimeImmutable $fecha,
        private readonly bool $justificada,
    ) {}

    public function fecha(): DateTimeImmutable
    {
        return $this->fecha;
    }

    public function justificada(): bool
    {
        return $this->justificada;
    }
}
