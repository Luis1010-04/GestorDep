<?php

namespace Src\Competencia\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Torneo de una temporada (HU-28): un catálogo compartido por todos los entrenadores. */
final class Torneo
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly Identificador $temporadaId,
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

    public function temporadaId(): Identificador
    {
        return $this->temporadaId;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
