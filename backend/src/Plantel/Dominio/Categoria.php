<?php

namespace Src\Plantel\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Categoría de equipos (HU-28): un catálogo compartido por todos los entrenadores. */
final class Categoria
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
