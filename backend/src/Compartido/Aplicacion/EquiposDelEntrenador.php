<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Qué equipos entrena un usuario, para contextos que no pueden leer las tablas de Plantel (docs/hitos/f1-h3.md §5.3).
 * Entrenar un equipo es tener una asignación vigente en él, con el equipo y el usuario no eliminados y el rol
 * `entrenador` (DA-13). El adaptador vive en Plantel.
 */
interface EquiposDelEntrenador
{
    /** @return list<Identificador> */
    public function equiposDe(Identificador $usuario): array;

    public function entrena(Identificador $usuario, Identificador $equipo): bool;
}
