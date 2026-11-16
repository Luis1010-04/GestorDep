<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * La última evaluación de cada entrenador sobre un jugador en un equipo, para Alertas, que no lee las tablas de Rendimiento
 * ni usa `CalculoDeDesempeno` (DA-112, DA-92). El adaptador vive en Rendimiento.
 */
interface EvaluacionesVigentesDelEquipo
{
    /** @return list<EvaluacionVigente> a lo sumo una por entrenador; vacía si nadie ha evaluado al jugador (DA-107) */
    public function de(Identificador $jugador, Identificador $equipo): array;
}
