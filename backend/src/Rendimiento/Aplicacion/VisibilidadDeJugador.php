<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Si el usuario de la sesión ve al jugador como entrenador (`AlcanceDeJugadores`, solo la rama del entrenador). */
interface VisibilidadDeJugador
{
    /** @param  bool  $incluirBajas  falso para capturar (un jugador en baja no se captura); verdadero para leer y corregir lo ya capturado (DA-12) */
    public function esVisibleParaEntrenador(Identificador $jugadorId, bool $incluirBajas): bool;
}
