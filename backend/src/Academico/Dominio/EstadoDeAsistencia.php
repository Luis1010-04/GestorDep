<?php

namespace Src\Academico\Dominio;

/** Estado de una asistencia a clase (HU-07, RN-05). La falta justificada no penaliza el porcentaje (DD-44). */
enum EstadoDeAsistencia: string
{
    case Presente = 'presente';
    case Falta = 'falta';
    case FaltaJustificada = 'falta_justificada';
}
