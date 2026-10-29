<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;

/** Porcentaje de asistencia del equipo y de sus jugadores vigentes (HU-11, criterio 3). */
final class VerAsistenciaDelEquipo
{
    public function __construct(
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
        private readonly JugadoresDelEquipo $jugadores,
        private readonly ConsultaDeAsistencia $consulta,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $equipoId): AsistenciaDelEquipo
    {
        $this->comprobarEquipo->ejecutar($equipoId);
        $jugadores = $this->jugadores->de($equipoId);
        $conteos = $this->consulta->deJugadoresEnEquipo($equipoId, array_map(fn (JugadorDelEquipo $jugador) => $jugador->id, $jugadores));

        return new AsistenciaDelEquipo(
            $equipoId,
            $this->consulta->delEquipo($equipoId),
            array_map(fn (JugadorDelEquipo $jugador) => ['jugador' => $jugador, 'conteo' => $conteos[$jugador->id->valor()]], $jugadores),
        );
    }
}
