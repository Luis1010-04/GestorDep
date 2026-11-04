<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\RepositorioDeAvisos;

/** El aviso existe y su jugador está en el alcance de entrenador del usuario (DD-61); si no, 404, antes que el 422 (§6.1). Admite jugadores en baja (DA-12). */
final class ComprobarAvisoDelEntrenador
{
    public function __construct(
        private readonly RepositorioDeAvisos $avisos,
        private readonly VisibilidadDeJugador $visibilidad,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $avisoId): void
    {
        $aviso = $this->avisos->buscar($avisoId);
        if ($aviso === null || ! $this->visibilidad->esVisibleComoEntrenador($aviso->jugadorId())) {
            throw new RecursoNoEncontrado;
        }
    }
}
