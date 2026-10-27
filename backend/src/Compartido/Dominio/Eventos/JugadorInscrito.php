<?php

namespace Src\Compartido\Dominio\Eventos;

/** Un jugador fue inscrito en una materia (HU-06). Solo identificadores. */
final class JugadorInscrito
{
    public function __construct(
        public readonly string $inscripcionId,
        public readonly string $materiaId,
        public readonly string $jugadorId,
    ) {}
}
