<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Los jugadores inscritos en una materia, para el escucha de `CriterioDeMateriaModificado`, que solo trae el identificador
 * de la materia (DA-115). El adaptador vive en Académico.
 */
interface JugadoresDeLaMateria
{
    /** @return list<Identificador> */
    public function de(Identificador $materia): array;
}
