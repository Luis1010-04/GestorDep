<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Posición de un jugador en un equipo (DD-05), para contextos que no pueden leer las tablas de Plantel
 * (docs/hitos/f1-h4.md §8, T02). Es la de su membresía vigente; sin membresía vigente, nula. El adaptador vive en Plantel.
 */
interface PosicionEnEquipo
{
    public function de(Identificador $jugador, Identificador $equipo): ?Identificador;
}
