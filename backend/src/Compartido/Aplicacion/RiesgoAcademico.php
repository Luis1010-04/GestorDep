<?php

namespace Src\Compartido\Aplicacion;

/** Nivel de riesgo académico de un jugador con los datos de la causa de la alerta (DA-105, DA-110). El promedio es un decimal en texto. */
final class RiesgoAcademico
{
    public function __construct(
        public readonly string $nivel,
        public readonly ?string $promedio,
        public readonly int $materiasReprobadas,
    ) {}
}
