<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Tarjetas de un jugador en los partidos de un equipo, para Alertas, que no lee las tablas de Rendimiento (DA-112). El
 * adaptador vive en Rendimiento. No filtra por entrenador: trae las filas de todos los entrenadores del equipo (DA-102),
 * y es Alertas quien se queda con el mayor número por partido y tipo (DA-103).
 */
interface TarjetasPorPartido
{
    /** @return list<TarjetasDeUnRegistro> una por fila de rendimiento no eliminada */
    public function de(Identificador $jugador, Identificador $equipo): array;
}
