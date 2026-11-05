<?php

namespace Src\Salud\Dominio;

/** Estados oficiales de disponibilidad (HU-20). */
enum TipoDeEstado: string
{
    case Apto = 'apto';
    case ConMolestia = 'con_molestia';
    case Lesionado = 'lesionado';
}
