<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Los gráficos de un equipo para su entrenador (HU-25 c2, DA-97): la evolución del grupo y, si se pidió un jugador, el
 * jugador contra el promedio del equipo. Los campos del jugador son nulos sin `filtro[jugador_id]`.
 */
final class GraficosDelEquipo
{
    public function __construct(
        public readonly SerieDeGrafico $rendimientoPartidos,
        public readonly SerieDeGrafico $asistenciaEntrenamientos,
        public readonly ?Identificador $jugadorId = null,
        public readonly ?SerieDeGrafico $rendimientoDelJugador = null,
        public readonly ?SerieDeGrafico $asistenciaDelJugador = null,
    ) {}
}
