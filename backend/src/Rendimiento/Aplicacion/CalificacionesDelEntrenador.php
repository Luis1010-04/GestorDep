<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Calificaciones generales que un entrenador registró de un jugador en un equipo, no eliminadas (RN-08, RN-14;
 * docs/hitos/f1-h4.md §3.3, v1.0.5).
 */
interface CalificacionesDelEntrenador
{
    /**
     * Del partido más reciente al más antiguo por su fecha de inicio, con desempate por el `id` del rendimiento.
     *
     * @return list<int>
     */
    public function de(Identificador $jugador, Identificador $equipo, Identificador $entrenador): array;
}
