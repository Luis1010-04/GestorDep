<?php

namespace Src\Academico\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Asignatura (HU-28): un catálogo compartido por todos los docentes. El rango del grado (1 a 12) se valida en la entrada (DD-51). */
final class Asignatura
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $clave,
        private readonly string $nombre,
        private readonly int $grado,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function clave(): string
    {
        return $this->clave;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function grado(): int
    {
        return $this->grado;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
