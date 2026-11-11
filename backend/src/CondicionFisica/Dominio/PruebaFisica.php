<?php

namespace Src\CondicionFisica\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Resultado de un jugador en un tipo de prueba física en un periodo (HU-18). El periodo se deriva de la fecha (DA-03).
 * El tipo identifica la fila (DA-04): una corrección cambia equipo, fecha y resultado, nunca el tipo. Solo la ve quien
 * la registró (DD-59).
 */
final class PruebaFisica
{
    private function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $equipoId,
        private readonly Identificador $registradoPor,
        private readonly Identificador $tipoPruebaId,
        private readonly string $fecha,
        private readonly NumeroDecimal $resultado,
        private readonly DateTimeImmutable $creadoEn,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function registrar(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $equipoId,
        Identificador $registradoPor,
        Identificador $tipoPruebaId,
        string $fecha,
        NumeroDecimal $resultado,
        DateTimeImmutable $instante,
    ): self {
        return new self($id, $jugadorId, $equipoId, $registradoPor, $tipoPruebaId, $fecha, $resultado, $instante, $instante);
    }

    public static function reconstruir(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $equipoId,
        Identificador $registradoPor,
        Identificador $tipoPruebaId,
        string $fecha,
        NumeroDecimal $resultado,
        DateTimeImmutable $creadoEn,
        DateTimeImmutable $actualizadoEn,
    ): self {
        return new self($id, $jugadorId, $equipoId, $registradoPor, $tipoPruebaId, $fecha, $resultado, $creadoEn, $actualizadoEn);
    }

    /** Una corrección puede cambiar el equipo (DA-13), no el tipo. */
    public function corregir(Identificador $equipoId, string $fecha, NumeroDecimal $resultado, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->jugadorId, $equipoId, $this->registradoPor, $this->tipoPruebaId, $fecha, $resultado, $this->creadoEn, $instante);
    }

    public function tieneLosMismosValoresQue(self $otra): bool
    {
        return $this->equipoId->igualA($otra->equipoId)
            && $this->fecha === $otra->fecha
            && $this->resultado->igualA($otra->resultado);
    }

    public function periodo(): string
    {
        return substr($this->fecha, 0, 7);
    }

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

    public function registradoPor(): Identificador
    {
        return $this->registradoPor;
    }

    public function tipoPruebaId(): Identificador
    {
        return $this->tipoPruebaId;
    }

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function resultado(): NumeroDecimal
    {
        return $this->resultado;
    }

    public function creadoEn(): DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
