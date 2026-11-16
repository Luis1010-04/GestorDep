<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Porcentaje de asistencia a entrenamientos realizados por mes (HU-25, DA-109, P-14). Lo calcula Competencia con su propia
 * regla (DD-51: la falta justificada cuenta en el denominador y no como presencia); Consultas lo lee sin repetir la
 * fórmula. El mes es el de la zona de presentación. El adaptador vive en la Infraestructura de Competencia.
 *
 * No comprueba el alcance: quien llama ya debe haberlo hecho.
 */
interface PorcentajeDeAsistenciaAEntrenamientos
{
    /** @param  list<Identificador>  $equipos  solo cuentan los entrenamientos de estos equipos */
    public function delJugadorEnEquipos(Identificador $jugador, array $equipos): AsistenciaPorMes;

    /** Todos los registros de asistencia de los jugadores del equipo. */
    public function delEquipo(Identificador $equipo): AsistenciaPorMes;
}
