<?php

namespace Src\Consultas\Aplicacion;

/** La serie de una materia del docente: sus calificaciones o su asistencia por mes (HU-25). */
final class SerieDeMateria
{
    public function __construct(
        public readonly MateriaDelGrafico $materia,
        public readonly SerieDeGrafico $serie,
    ) {}
}
