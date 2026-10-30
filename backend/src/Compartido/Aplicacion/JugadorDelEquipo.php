<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lo que `JugadoresDelEquipo` revela de cada jugador: lo mínimo para tomar asistencia (DA-12). */
final class JugadorDelEquipo
{
    public function __construct(
        public readonly Identificador $id,
        public readonly string $nombres,
        public readonly string $apellidos,
    ) {}
}
