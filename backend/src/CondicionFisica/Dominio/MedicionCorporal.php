<?php

namespace Src\CondicionFisica\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Peso y estatura de un jugador en un periodo (HU-17). El periodo se deriva de la fecha (DA-03) y el IMC se calcula, nunca
 * se captura (DA-05). Solo la ve quien la registró (DD-59).
 */
final class MedicionCorporal
{
    private function __construct(
        private readonly Identificador $id,
        private readonly Identificador $jugadorId,
        private readonly Identificador $equipoId,
        private readonly Identificador $registradoPor,
        private readonly string $fecha,
        private readonly NumeroDecimal $pesoKg,
        private readonly NumeroDecimal $estaturaCm,
        private readonly DateTimeImmutable $creadoEn,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public static function registrar(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $equipoId,
        Identificador $registradoPor,
        string $fecha,
        NumeroDecimal $pesoKg,
        NumeroDecimal $estaturaCm,
        DateTimeImmutable $instante,
    ): self {
        return new self($id, $jugadorId, $equipoId, $registradoPor, $fecha, $pesoKg, $estaturaCm, $instante, $instante);
    }

    public static function reconstruir(
        Identificador $id,
        Identificador $jugadorId,
        Identificador $equipoId,
        Identificador $registradoPor,
        string $fecha,
        NumeroDecimal $pesoKg,
        NumeroDecimal $estaturaCm,
        DateTimeImmutable $creadoEn,
        DateTimeImmutable $actualizadoEn,
    ): self {
        return new self($id, $jugadorId, $equipoId, $registradoPor, $fecha, $pesoKg, $estaturaCm, $creadoEn, $actualizadoEn);
    }

    /** Una corrección puede cambiar el equipo (DA-13). */
    public function corregir(Identificador $equipoId, string $fecha, NumeroDecimal $pesoKg, NumeroDecimal $estaturaCm, DateTimeImmutable $instante): self
    {
        return new self($this->id, $this->jugadorId, $equipoId, $this->registradoPor, $fecha, $pesoKg, $estaturaCm, $this->creadoEn, $instante);
    }

    public function tieneLosMismosValoresQue(self $otra): bool
    {
        return $this->equipoId->igualA($otra->equipoId)
            && $this->fecha === $otra->fecha
            && $this->pesoKg->igualA($otra->pesoKg)
            && $this->estaturaCm->igualA($otra->estaturaCm);
    }

    public function periodo(): string
    {
        return substr($this->fecha, 0, 7);
    }

    /** `peso_kg ÷ (estatura_cm ÷ 100)²`, a dos decimales HALF_UP (DA-05). La estatura en metros es exacta con tres decimales. */
    public function imc(): NumeroDecimal
    {
        $metros = $this->estaturaCm->dividir(NumeroDecimal::desdeTexto('100'), 3);

        return $this->pesoKg->dividir($metros->multiplicar($metros), 2);
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

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function pesoKg(): NumeroDecimal
    {
        return $this->pesoKg;
    }

    public function estaturaCm(): NumeroDecimal
    {
        return $this->estaturaCm;
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
