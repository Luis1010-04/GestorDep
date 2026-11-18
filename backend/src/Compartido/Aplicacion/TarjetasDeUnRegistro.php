<?php

namespace Src\Compartido\Aplicacion;

/** Las tarjetas de una fila de rendimiento de un partido, registrada por un entrenador (DA-102, DA-103). */
final class TarjetasDeUnRegistro
{
    public function __construct(
        public readonly string $partidoId,
        public readonly int $amarillas,
        public readonly int $rojas,
    ) {}
}
