<?php

namespace Src\Academico\Dominio;

/** Alcance de un criterio de aprobación (RN-01, RN-03): el general de la institución o el propio de una materia. */
enum AmbitoDeCriterio: string
{
    case General = 'general';
    case Materia = 'materia';
}
