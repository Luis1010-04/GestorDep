<?php

namespace Src\Plantel\Aplicacion;

use Src\Plantel\Dominio\Jugador;

/** Lectura de un jugador recién registrado con el equipo, su categoría y la posición en él. */
final class JugadorRegistrado
{
    public function __construct(
        public readonly Jugador $jugador,
        public readonly EquipoConCategoria $equipo,
        public readonly string $posicion,
    ) {}
}
