<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Si el usuario de la sesión ve al jugador como entrenador, con bajas incluidas (`AlcanceDeJugadores`, DA-20). */
interface VisibilidadDeJugador
{
    public function esVisibleParaEntrenador(Identificador $jugadorId): bool;
}
