<?php

namespace Src\Competencia\Dominio;

/** Condición del equipo en el partido (HU-12). */
enum CondicionDePartido: string
{
    case Local = 'local';
    case Visitante = 'visitante';
}
