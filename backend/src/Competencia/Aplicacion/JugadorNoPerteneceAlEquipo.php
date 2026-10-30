<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\ReglaDeCampoViolada;

/** El jugador no tiene membresía vigente en el equipo o está dado de baja (§3.4); el campo señala el registro (DA-16). */
final class JugadorNoPerteneceAlEquipo extends ReglaDeCampoViolada
{
    public function __construct(string $campo)
    {
        parent::__construct('jugador_no_pertenece_al_equipo', $campo);
    }
}
