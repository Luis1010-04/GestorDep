<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Jugadores con membresía vigente y sin baja de un equipo, para contextos que no pueden leer las tablas de Plantel
 * (docs/hitos/f1-h3.md §5.3). El adaptador vive en Plantel y pasa por `AlcanceDeJugadores` con el usuario de la
 * sesión: a quien no entrena el equipo no le devuelve a nadie (DA-12).
 */
interface JugadoresDelEquipo
{
    /** @return list<JugadorDelEquipo> ordenados por apellidos, nombres e id */
    public function de(Identificador $equipo): array;
}
