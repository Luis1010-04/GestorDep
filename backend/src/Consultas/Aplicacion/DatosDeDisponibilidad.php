<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;

/** El estado oficial vigente, sin detalle de la lesión (RN-15); `apto` implícito si no hay ninguno, con `actualizadoEn` nulo. */
final class DatosDeDisponibilidad
{
    public function __construct(
        public readonly string $estado,
        public readonly ?string $fechaEstimadaRegreso,
        public readonly bool $avisoPendiente,
        public readonly ?DateTimeImmutable $actualizadoEn,
    ) {}
}
