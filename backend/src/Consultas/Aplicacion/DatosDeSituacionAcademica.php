<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;

/** El resumen académico simplificado del periodo vigente, sin materias (RN-13). Sin periodo vigente, `periodo` es nulo. */
final class DatosDeSituacionAcademica
{
    /** @param  array{id: string, nombre: string}|null  $periodo */
    public function __construct(
        public readonly ?array $periodo,
        public readonly ?string $promedio,
        public readonly int $materiasReprobadas,
        public readonly int $materiasSinCalificar,
        public readonly bool $datoIncompleto,
        public readonly ?string $porcentajeAsistencia,
        public readonly ?string $nivelRiesgo,
        public readonly ?DateTimeImmutable $calculadoEn,
    ) {}
}
