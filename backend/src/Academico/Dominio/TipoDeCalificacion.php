<?php

namespace Src\Academico\Dominio;

/** Una calificación parcial (con número de parcial) o la final de la materia (RN-02). */
enum TipoDeCalificacion: string
{
    case Parcial = 'parcial';
    case Final = 'final';
}
