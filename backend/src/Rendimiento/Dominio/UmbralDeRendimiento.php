<?php

namespace Src\Rendimiento\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Umbral propio de un jugador en un equipo, de un entrenador (HU-15, DA-04). Guarda sus valores copiados; la plantilla,
 * si la hay, es solo la procedencia. Un umbral nuevo cierra el vigente en el instante en que empieza.
 */
final class UmbralDeRendimiento
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $equipoId,
        private readonly Identificador $entrenadorId,
        private readonly ?Identificador $plantillaId,
        private readonly CriterioDeEvaluacion $criterio,
        private readonly DateTimeImmutable $vigenteDesde,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function equipoId(): Identificador
    {
        return $this->equipoId;
    }

    public function entrenadorId(): Identificador
    {
        return $this->entrenadorId;
    }

    public function plantillaId(): ?Identificador
    {
        return $this->plantillaId;
    }

    public function criterio(): CriterioDeEvaluacion
    {
        return $this->criterio;
    }

    public function vigenteDesde(): DateTimeImmutable
    {
        return $this->vigenteDesde;
    }
}
