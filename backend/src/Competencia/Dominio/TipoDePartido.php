<?php

namespace Src\Competencia\Dominio;

/** Tipo de partido (HU-12). */
enum TipoDePartido: string
{
    case Amistoso = 'amistoso';
    case Oficial = 'oficial';
    case Interno = 'interno';
    case Torneo = 'torneo';

    /** El torneo es obligatorio en partidos oficiales y de torneo, y opcional en amistosos e internos (§3.4). */
    public function exigeTorneo(): bool
    {
        return $this === self::Oficial || $this === self::Torneo;
    }
}
