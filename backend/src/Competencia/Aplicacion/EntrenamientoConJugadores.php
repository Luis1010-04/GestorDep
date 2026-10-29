<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Competencia\Dominio\Entrenamiento;

/** Un entrenamiento con los jugadores del equipo listados para la asistencia (HU-10) y si su lugar está empalmado. */
final class EntrenamientoConJugadores
{
    /** @param  list<JugadorDelEquipo>  $jugadores */
    public function __construct(
        public readonly Entrenamiento $entrenamiento,
        public readonly array $jugadores,
        public readonly bool $empalmado = false,
    ) {}
}
