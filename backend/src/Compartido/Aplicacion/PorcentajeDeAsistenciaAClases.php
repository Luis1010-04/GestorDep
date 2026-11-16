<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Porcentaje de asistencia a clases por mes de un jugador en una materia (HU-25, DA-109, P-14). Lo calcula Académico con su
 * propia regla (RN-05: la falta justificada cuenta como presencia); Consultas lo lee sin repetir la fórmula. El adaptador vive
 * en la Infraestructura de Académico.
 *
 * No comprueba el alcance: quien llama ya debe haberlo hecho.
 */
interface PorcentajeDeAsistenciaAClases
{
    public function delJugadorEnLaMateria(Identificador $jugador, Identificador $materia): AsistenciaPorMes;
}
