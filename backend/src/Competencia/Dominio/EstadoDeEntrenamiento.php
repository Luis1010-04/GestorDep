<?php

namespace Src\Competencia\Dominio;

/** Estado de un entrenamiento (HU-10): solo se sale de `programado`, hacia `realizado` o `cancelado` (§3.4). */
enum EstadoDeEntrenamiento: string
{
    case Programado = 'programado';
    case Realizado = 'realizado';
    case Cancelado = 'cancelado';
}
