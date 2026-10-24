<?php

namespace Src\Plantel\Aplicacion;

/** Un equipo del entrenador que consulta donde el jugador tiene membresía vigente, con su categoría y su posición. */
final class EquipoDelJugador
{
    public function __construct(
        public readonly string $equipoId,
        public readonly string $nombre,
        public readonly string $categoriaId,
        public readonly string $categoriaNombre,
        public readonly string $posicion,
    ) {}
}
