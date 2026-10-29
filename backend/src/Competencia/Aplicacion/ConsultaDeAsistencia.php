<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;

/** Conteos de asistencia, solo sobre entrenamientos `realizado` (§3.4). */
interface ConsultaDeAsistencia
{
    /** Todos los registros de los entrenamientos realizados del equipo (DA-21). */
    public function delEquipo(Identificador $equipoId): ConteoDeAsistenciaAEntrenamientos;

    /**
     * @param  list<Identificador>  $jugadores
     * @return array<string, ConteoDeAsistenciaAEntrenamientos> por id de jugador; todos los pedidos, con ceros si no tienen registros
     */
    public function deJugadoresEnEquipo(Identificador $equipoId, array $jugadores): array;

    /**
     * Registros del jugador en los entrenamientos realizados de los equipos indicados (§3.4: solo los del entrenador).
     *
     * @param  list<Identificador>  $equipos
     */
    public function delJugadorEnEquipos(Identificador $jugadorId, array $equipos): ConteoDeAsistenciaAEntrenamientos;
}
