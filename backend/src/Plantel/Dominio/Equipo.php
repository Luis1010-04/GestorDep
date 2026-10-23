<?php

namespace Src\Plantel\Dominio;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Equipo de una categoría (HU-03). Quien lo crea queda asignado como entrenador (RN-22). */
final class Equipo
{
    public function __construct(
        private readonly Identificador $id,
        private readonly string $nombre,
        private readonly Identificador $categoriaId,
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

    public function categoriaId(): Identificador
    {
        return $this->categoriaId;
    }

    public function actualizadoEn(): DateTimeImmutable
    {
        return $this->actualizadoEn;
    }
}
