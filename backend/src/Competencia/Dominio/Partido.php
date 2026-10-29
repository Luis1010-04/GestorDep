<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use DateTimeZone;
use Src\Compartido\Dominio\Identificador;

/**
 * Partido de un equipo (HU-12). Inmutable: cada cambio devuelve otra instancia. La categoría se toma del equipo
 * (DD-18). Solo cambia mientras está `programado`; de ahí pasa a `jugado` o `cancelado` (§3.4).
 */
final class Partido
{
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $equipoId,
        private readonly Identificador $temporadaId,
        private readonly ?Identificador $torneoId,
        private readonly Identificador $rivalId,
        private readonly Identificador $lugarId,
        private readonly DateTimeImmutable $iniciaEn,
        private readonly TipoDePartido $tipo,
        private readonly CondicionDePartido $condicion,
        private readonly EstadoDePartido $estado,
        private readonly ?int $golesFavor,
        private readonly ?int $golesContra,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    /** Medianoche del día de `iniciaEn` en la zona dada: el día que decide la temporada (§3.4, RNF-04). */
    public function diaLocal(DateTimeZone $zona): DateTimeImmutable
    {
        return self::diaLocalDe($this->iniciaEn, $zona);
    }

    /** El mismo cálculo para un `inicia_en` que todavía no es de un partido (alta o cambio de fecha). */
    public static function diaLocalDe(DateTimeImmutable $iniciaEn, DateTimeZone $zona): DateTimeImmutable
    {
        return $iniciaEn->setTimezone($zona)->setTime(0, 0);
    }

    /**
     * Reemplaza los datos de alta; la temporada ya viene derivada y el torneo validado por la aplicación.
     *
     * @throws PartidoNoProgramado
     */
    public function conDatos(
        Identificador $temporadaId,
        ?Identificador $torneoId,
        Identificador $rivalId,
        Identificador $lugarId,
        DateTimeImmutable $iniciaEn,
        TipoDePartido $tipo,
        CondicionDePartido $condicion,
        DateTimeImmutable $instante,
    ): self {
        $this->exigirProgramado();

        return new self($this->id, $this->equipoId, $temporadaId, $torneoId, $rivalId, $lugarId, $iniciaEn, $tipo, $condicion, $this->estado, null, null, $instante);
    }

    /** @throws TransicionNoPermitida si no está `programado` */
    public function cancelado(DateTimeImmutable $instante): self
    {
        if ($this->estado !== EstadoDePartido::Programado) {
            throw new TransicionNoPermitida;
        }

        return $this->conEstado(EstadoDePartido::Cancelado, null, null, $instante);
    }

    /**
     * El resultado pasa el partido a `jugado`; un partido jugado o cancelado no lo admite (§3.4). La captura por la
     * API llega en T07.
     *
     * @throws PartidoNoProgramado
     */
    public function conResultado(int $golesFavor, int $golesContra, DateTimeImmutable $instante): self
    {
        $this->exigirProgramado();

        return $this->conEstado(EstadoDePartido::Jugado, $golesFavor, $golesContra, $instante);
    }

    /** @throws PartidoNoProgramado */
    public function exigirProgramado(): void
    {
        if ($this->estado !== EstadoDePartido::Programado) {
            throw new PartidoNoProgramado;
        }
    }

    private function conEstado(EstadoDePartido $estado, ?int $golesFavor, ?int $golesContra, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->equipoId, $this->temporadaId, $this->torneoId, $this->rivalId, $this->lugarId, $this->iniciaEn, $this->tipo, $this->condicion, $estado, $golesFavor, $golesContra, $instante);
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function equipoId(): Identificador
    {
        return $this->equipoId;
    }

    public function temporadaId(): Identificador
    {
        return $this->temporadaId;
    }

    public function torneoId(): ?Identificador
    {
        return $this->torneoId;
    }

    public function rivalId(): Identificador
    {
        return $this->rivalId;
    }

    public function lugarId(): Identificador
    {
        return $this->lugarId;
    }

    public function iniciaEn(): DateTimeImmutable
    {
        return $this->iniciaEn;
    }

    public function tipo(): TipoDePartido
    {
        return $this->tipo;
    }

    public function condicion(): CondicionDePartido
    {
        return $this->condicion;
    }

    public function estado(): EstadoDePartido
    {
        return $this->estado;
    }

    public function golesFavor(): ?int
    {
        return $this->golesFavor;
    }

    public function golesContra(): ?int
    {
        return $this->golesContra;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
