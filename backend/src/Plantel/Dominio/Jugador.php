<?php

namespace Src\Plantel\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Expediente de un jugador (HU-03). Al registrarse queda activo en lo académico y en lo deportivo. */
final class Jugador
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombres,
        private readonly string $apellidos,
        private readonly DateTimeImmutable $fechaNacimiento,
        private readonly string $matricula,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function nombres(): string
    {
        return $this->nombres;
    }

    public function apellidos(): string
    {
        return $this->apellidos;
    }

    public function fechaNacimiento(): DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function matricula(): string
    {
        return $this->matricula;
    }

    public function estatusAcademico(): string
    {
        return 'activo';
    }

    public function estatusDeportivo(): string
    {
        return 'activo';
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
