<?php

namespace Src\Rendimiento\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Una evaluación de un jugador en un equipo para un entrenador (HU-16, DA-04). Guarda el criterio aplicado copiado y,
 * como procedencia, el umbral o la plantilla de los que salió (v1.0.2). Solo se inserta (§5.1).
 */
final class EvaluacionDeRendimiento
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $equipoId,
        private readonly Identificador $entrenadorId,
        private readonly ?Identificador $umbralId,
        private readonly ?Identificador $plantillaId,
        private readonly CriterioDeEvaluacion $criterio,
        private readonly EvaluacionCalculada $calculada,
        private readonly DateTimeImmutable $calculadaEn,
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

    public function umbralId(): ?Identificador
    {
        return $this->umbralId;
    }

    public function plantillaId(): ?Identificador
    {
        return $this->plantillaId;
    }

    public function criterio(): CriterioDeEvaluacion
    {
        return $this->criterio;
    }

    public function calculada(): EvaluacionCalculada
    {
        return $this->calculada;
    }

    public function calculadaEn(): DateTimeImmutable
    {
        return $this->calculadaEn;
    }
}
