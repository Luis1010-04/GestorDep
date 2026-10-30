<?php

namespace Src\Competencia\Dominio;

/** Estado de la asistencia de un jugador a un entrenamiento (RN-11). */
enum EstadoDeAsistencia: string
{
    case Presente = 'presente';
    case Falta = 'falta';
    case FaltaJustificada = 'falta_justificada';
}
