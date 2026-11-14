<?php

namespace Src\Salud\Dominio;

/** Causas de una lesión (§5.1). */
enum CausaDeLesion: string
{
    case Entrenamiento = 'entrenamiento';
    case Partido = 'partido';
    case Otra = 'otra';
}
