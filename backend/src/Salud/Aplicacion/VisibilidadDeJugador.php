<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Si el usuario de la sesión ve al jugador (`AlcanceDeJugadores`), sin leer tablas de Plantel. */
interface VisibilidadDeJugador
{
    /** Como entrenador o como docente. Con `$incluirBajas` en falso, un jugador en baja no cuenta. */
    public function esVisible(Identificador $jugadorId, bool $incluirBajas): bool;

    /** Solo como entrenador, incluidos los jugadores en baja: la baja no cierra membresías (DD-30, DA-12). */
    public function esVisibleComoEntrenador(Identificador $jugadorId): bool;
}
