<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Si el usuario de la sesión entrena al jugador (`AlcanceDeJugadores`), sin leer tablas de Plantel. */
interface VisibilidadDeJugador
{
    /** Solo como entrenador (§5.3). Con `$incluirBajas` en falso, un jugador en baja no cuenta. */
    public function esVisibleComoEntrenador(Identificador $jugadorId, bool $incluirBajas): bool;
}
