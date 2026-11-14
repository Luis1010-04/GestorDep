<?php

namespace Src\Salud\Dominio;

/** Estados de un aviso de lesión (RN-16, DD-56). */
enum EstadoDeAviso: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case Descartado = 'descartado';
}
