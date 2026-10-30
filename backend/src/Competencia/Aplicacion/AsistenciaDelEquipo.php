<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;

/** Asistencia del equipo y de cada jugador vigente (HU-11, criterio 3; DA-21). */
final class AsistenciaDelEquipo
{
    /** @param  list<array{jugador: JugadorDelEquipo, conteo: ConteoDeAsistenciaAEntrenamientos}>  $porJugador */
    public function __construct(
        public readonly Identificador $equipoId,
        public readonly ConteoDeAsistenciaAEntrenamientos $conteo,
        public readonly array $porJugador,
    ) {}
}
