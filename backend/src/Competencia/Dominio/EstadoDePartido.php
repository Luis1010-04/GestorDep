<?php

namespace Src\Competencia\Dominio;

/** Estado de un partido: solo se sale de `programado`, hacia `jugado` (al capturar el resultado) o `cancelado` (§3.4). */
enum EstadoDePartido: string
{
    case Programado = 'programado';
    case Jugado = 'jugado';
    case Cancelado = 'cancelado';
}
