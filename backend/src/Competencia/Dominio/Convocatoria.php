<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Un jugador convocado a un partido (HU-12). La posición y el rol llegan con la alineación (T07); mientras tanto son
 * nulos (DA-03). Quitarlo de la convocatoria es un borrado lógico que se lleva su alineación.
 */
final class Convocatoria
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $partidoId,
        private readonly Identificador $jugadorId,
        private readonly ?Identificador $posicionId,
        private readonly ?RolDeAlineacion $rolAlineacion,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function nueva(Identificador $partidoId, Identificador $jugadorId, DateTimeImmutable $instante): self
    {
        return new self(Identificador::generar(), $partidoId, $jugadorId, null, null, $instante);
    }

    /** Fija la posición y el rol de la alineación, o los quita con nulos (HU-13). */
    public function conAlineacion(?Identificador $posicionId, ?RolDeAlineacion $rol, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->partidoId, $this->jugadorId, $posicionId, $rol, $instante);
    }

    /** Si la posición y el rol son los mismos que `$posicionId` y `$rol`: un reemplazo sin cambio no escribe historial. */
    public function tieneAlineacion(?Identificador $posicionId, ?RolDeAlineacion $rol): bool
    {
        $mismaPosicion = $posicionId === null ? $this->posicionId === null : $this->posicionId?->igualA($posicionId) === true;

        return $mismaPosicion && $this->rolAlineacion === $rol;
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function partidoId(): Identificador
    {
        return $this->partidoId;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function posicionId(): ?Identificador
    {
        return $this->posicionId;
    }

    public function rolAlineacion(): ?RolDeAlineacion
    {
        return $this->rolAlineacion;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
