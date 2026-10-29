<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** Un jugador de la alineación no está en la convocatoria visible del partido (HU-13, DA-30, DA-32). */
final class JugadorNoConvocado extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('jugador_no_convocado', $campo);
    }
}
