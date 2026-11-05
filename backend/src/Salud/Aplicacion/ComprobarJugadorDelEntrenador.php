<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** El usuario entrena al jugador; si no, 404, antes que el 422 (§6.1). El rol `docente` no basta (DD-61). Admite jugadores en baja (DA-12). */
final class ComprobarJugadorDelEntrenador
{
    public function __construct(private readonly VisibilidadDeJugador $visibilidad) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $jugadorId): void
    {
        if (! $this->visibilidad->esVisibleComoEntrenador($jugadorId)) {
            throw new RecursoNoEncontrado;
        }
    }
}
