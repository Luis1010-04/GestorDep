<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Si el usuario de la sesión ve al jugador como entrenador (`AlcanceDeJugadores`, DA-20, DA-34). */
interface VisibilidadDeJugador
{
    /** @param  bool  $incluirBajas  verdadero para el historial (DA-20); falso donde el jugador en baja ya no aparece (DA-34) */
    public function esVisibleParaEntrenador(Identificador $jugadorId, bool $incluirBajas = true): bool;
}
