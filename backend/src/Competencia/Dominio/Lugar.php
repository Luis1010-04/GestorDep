<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Lugar de entrenamientos y partidos (HU-28): un catálogo compartido por todos los entrenadores. */
final class Lugar
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly DateTimeImmutable $actualizadoEn,
    ) {}

    public function id(): Identificador
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
