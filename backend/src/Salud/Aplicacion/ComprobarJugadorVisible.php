<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** El jugador existe y está en el alcance del usuario, como entrenador o como docente; si no, 404 (§6.1). Admite jugadores en baja (DD-30). */
final class ComprobarJugadorVisible
{
    public function __construct(private readonly VisibilidadDeJugador $visibilidad) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $jugadorId): void
    {
        if (! $this->visibilidad->esVisible($jugadorId, incluirBajas: true)) {
            throw new RecursoNoEncontrado;
        }
    }
}
