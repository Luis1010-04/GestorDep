<?php

namespace Src\Alertas\Dominio;

/** Qué hay que hacer con la alerta de un tipo, jugador y equipo tras recalcular su condición (HU-22). */
enum AccionSobreAlerta: string
{
    case Abrir = 'abrir';
    case Cerrar = 'cerrar';
    case Ninguna = 'ninguna';
}
