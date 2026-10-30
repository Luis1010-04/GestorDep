<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;

/**
 * Plantilla de evaluación de una posición (RN-07): de fábrica (sin autor) o propia de un entrenador. En H4 ninguna se
 * edita ni se borra desde la API (v1.0.4 §2.3).
 */
final class PlantillaDeEvaluacion
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly Identificador $posicionId,
        private readonly ?Identificador $creadaPor,
        private readonly CriterioDeEvaluacion $criterio,
    ) {}

    public static function propia(Identificador $id, string $nombre, Identificador $posicionId, Identificador $autor, CriterioDeEvaluacion $criterio): self
    {
        return new self($id, $nombre, $posicionId, $autor, $criterio);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function posicionId(): Identificador
    {
        return $this->posicionId;
    }

    public function creadaPor(): ?Identificador
    {
        return $this->creadaPor;
    }

    public function esDeFabrica(): bool
    {
        return $this->creadaPor === null;
    }

    public function criterio(): CriterioDeEvaluacion
    {
        return $this->criterio;
    }
}
