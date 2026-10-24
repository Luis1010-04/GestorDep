<?php

namespace Src\Plantel\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de jugadores y de su membresía en un equipo. Solo ve lo no eliminado. */
interface RepositorioDeJugadores
{
    /** La matrícula no distingue mayúsculas; el llamador la entrega ya recortada. */
    public function existeMatricula(string $matricula): bool;

    /**
     * Guarda el jugador y su membresía vigente en el equipo (`desde` es hoy en la zona de la institución), con sus
     * historiales, en una transacción. Si otra petición ganó la carrera por la matrícula, lanza MatriculaDuplicada (Aplicacion).
     */
    public function guardarNuevo(Jugador $jugador, Identificador $equipoId, Identificador $posicionId): void;
}
