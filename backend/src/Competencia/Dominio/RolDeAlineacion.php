<?php

namespace Src\Competencia\Dominio;

/** Rol de un convocado en la alineación (HU-13); nulo mientras no haya alineación. */
enum RolDeAlineacion: string
{
    case Titular = 'titular';
    case Suplente = 'suplente';
}
