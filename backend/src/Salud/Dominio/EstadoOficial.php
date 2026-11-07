<?php

namespace Src\Salud\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/**
 * Estado oficial de disponibilidad de un jugador (HU-20, HU-21). `confirmadoPor` es quien lo sostiene, el único que
 * puede modificarlo (RN-16, DA-19). La fecha estimada de regreso vigente vive aquí y decide si requiere actualización
 * (DA-02, DA-09). Un cambio de estado cierra esta fila y abre su sucesora; un cambio de fecha actualiza esta (DA-14).
 * `actualizadoEn` es el momento de su último cambio, sin decir cuál fue (RNF-04, DA-56).
 */
final class EstadoOficial
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly TipoDeEstado $tipo,
        private readonly ?Identificador $lesionId,
        private readonly ?string $fechaEstimadaRegreso,
        private readonly Identificador $confirmadoPor,
        private readonly DateTimeImmutable $vigenteDesde,
        private readonly ?DateTimeImmutable $vigenteHasta,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function apto(Identificador $id, Identificador $jugadorId, Identificador $confirmadoPor, DateTimeImmutable $instante): self
    {
        return new self($id, $jugadorId, TipoDeEstado::Apto, null, null, $confirmadoPor, $instante, null, $instante);
    }

    public static function conLesion(
        Identificador $id,
        Identificador $jugadorId,
        TipoDeEstado $tipo,
        Identificador $lesionId,
        string $fechaEstimadaRegreso,
        Identificador $confirmadoPor,
        DateTimeImmutable $instante,
    ): self {
        return new self($id, $jugadorId, $tipo, $lesionId, $fechaEstimadaRegreso, $confirmadoPor, $instante, null, $instante);
    }

    /** @throws EstadoEnElMismoInstante */
    public function cerrar(DateTimeImmutable $instante): self
    {
        if ($instante <= $this->vigenteDesde) {
            throw new EstadoEnElMismoInstante;
        }

        return new self($this->id, $this->jugadorId, $this->tipo, $this->lesionId, $this->fechaEstimadaRegreso, $this->confirmadoPor, $this->vigenteDesde, $instante, $instante);
    }

    public function conFecha(string $fechaEstimadaRegreso, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->jugadorId, $this->tipo, $this->lesionId, $fechaEstimadaRegreso, $this->confirmadoPor, $this->vigenteDesde, $this->vigenteHasta, $instante);
    }

    /** El estado que abre un cambio de tipo: misma lesión y mismo registrante (DA-14). */
    public function sucesor(Identificador $id, TipoDeEstado $tipo, ?string $fechaEstimadaRegreso, DateTimeImmutable $instante): self
    {
        return new self($id, $this->jugadorId, $tipo, $this->lesionId, $fechaEstimadaRegreso, $this->confirmadoPor, $instante, null, $instante);
    }

    /** @throws SinLesionVigente|NoEsRegistrante */
    public function exigirModificablePor(Identificador $usuario): void
    {
        if ($this->tipo === TipoDeEstado::Apto) {
            throw new SinLesionVigente;
        }
        if (! $this->esSostenidoPor($usuario)) {
            throw new NoEsRegistrante;
        }
    }

    public function esSostenidoPor(Identificador $usuario): bool
    {
        return $this->confirmadoPor->igualA($usuario);
    }

    /** `$hoy` en `AAAA-MM-DD`; se calcula al leer y no genera alerta (DA-09). */
    public function requiereActualizacion(string $hoy): bool
    {
        return $this->fechaEstimadaRegreso !== null && $this->fechaEstimadaRegreso < $hoy;
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function jugadorId(): Identificador
    {
        return $this->jugadorId;
    }

    public function tipo(): TipoDeEstado
    {
        return $this->tipo;
    }

    public function lesionId(): ?Identificador
    {
        return $this->lesionId;
    }

    public function fechaEstimadaRegreso(): ?string
    {
        return $this->fechaEstimadaRegreso;
    }

    public function confirmadoPor(): Identificador
    {
        return $this->confirmadoPor;
    }

    public function vigenteDesde(): DateTimeImmutable
    {
        return $this->vigenteDesde;
    }

    public function vigenteHasta(): ?DateTimeImmutable
    {
        return $this->vigenteHasta;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
