<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * Sesión de entrenamiento de un equipo (HU-10). Inmutable: cada cambio devuelve otra instancia. Hora, lugar y
 * responsable solo cambian mientras está `programado` (DA-08); el estado solo sale de `programado` (§3.4).
 */
final class Entrenamiento
{
    /** @throws ReglaDeNegocioViolada `rango_de_fechas_invalido` si `terminaEn` no es posterior a `iniciaEn` */
    public function __construct(
        private readonly Identificador $id,
        private readonly Identificador $equipoId,
        private readonly Identificador $lugarId,
        private readonly DateTimeImmutable $iniciaEn,
        private readonly DateTimeImmutable $terminaEn,
        private readonly Identificador $responsableId,
        private readonly EstadoDeEntrenamiento $estado,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {
        if ($terminaEn <= $iniciaEn) {
            throw new ReglaDeNegocioViolada('rango_de_fechas_invalido');
        }
    }

    /**
     * Los argumentos nulos conservan el valor actual.
     *
     * @throws EntrenamientoNoProgramado|ReglaDeNegocioViolada
     */
    public function conDatos(
        ?DateTimeImmutable $iniciaEn,
        ?DateTimeImmutable $terminaEn,
        ?Identificador $lugarId,
        ?Identificador $responsableId,
        DateTimeImmutable $instante,
    ): self {
        if ($this->estado !== EstadoDeEntrenamiento::Programado) {
            throw new EntrenamientoNoProgramado;
        }

        return new self(
            $this->id,
            $this->equipoId,
            $lugarId ?? $this->lugarId,
            $iniciaEn ?? $this->iniciaEn,
            $terminaEn ?? $this->terminaEn,
            $responsableId ?? $this->responsableId,
            $this->estado,
            $instante,
        );
    }

    /** @throws TransicionNoPermitida si no sale de `programado` hacia `realizado` o `cancelado` */
    public function conEstado(EstadoDeEntrenamiento $estado, DateTimeImmutable $instante): self
    {
        if ($this->estado !== EstadoDeEntrenamiento::Programado || $estado === EstadoDeEntrenamiento::Programado) {
            throw new TransicionNoPermitida;
        }

        return new self($this->id, $this->equipoId, $this->lugarId, $this->iniciaEn, $this->terminaEn, $this->responsableId, $estado, $instante);
    }

    public function cuentaParaEmpalmes(): bool
    {
        return $this->estado !== EstadoDeEntrenamiento::Cancelado;
    }

    public function id(): Identificador
    {
        return $this->id;
    }

    public function equipoId(): Identificador
    {
        return $this->equipoId;
    }

    public function lugarId(): Identificador
    {
        return $this->lugarId;
    }

    public function iniciaEn(): DateTimeImmutable
    {
        return $this->iniciaEn;
    }

    public function terminaEn(): DateTimeImmutable
    {
        return $this->terminaEn;
    }

    public function responsableId(): Identificador
    {
        return $this->responsableId;
    }

    public function estado(): EstadoDeEntrenamiento
    {
        return $this->estado;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
