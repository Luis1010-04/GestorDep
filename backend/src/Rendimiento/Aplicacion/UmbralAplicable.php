<?php

namespace Src\Rendimiento\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\CriterioDeEvaluacion;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;
use Src\Rendimiento\Dominio\UmbralDeRendimiento;

/** El criterio que se aplica a un jugador para un entrenador: su umbral vigente o la plantilla de fábrica de su posición (HU-15, v1.0.4 §6.2). */
final class UmbralAplicable
{
    private function __construct(
        public readonly string $origen,
        public readonly ?Identificador $plantillaId,
        public readonly CriterioDeEvaluacion $criterio,
        public readonly ?DateTimeImmutable $vigenteDesde,
    ) {}

    public static function desdeUmbral(UmbralDeRendimiento $umbral): self
    {
        return new self('umbral', $umbral->plantillaId(), $umbral->criterio(), $umbral->vigenteDesde());
    }

    public static function desdePlantilla(PlantillaDeEvaluacion $plantilla): self
    {
        return new self('plantilla', $plantilla->id(), $plantilla->criterio(), null);
    }
}
