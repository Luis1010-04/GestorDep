<?php

namespace Src\Competencia\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Aplicacion\ConsultaDeAsistencia;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;
use stdClass;

/** Conteos por estado sobre los registros de entrenamientos `realizado` (§3.4). */
final class ConsultaDeAsistenciaSql implements ConsultaDeAsistencia
{
    private const CONTEOS = "count(*) filter (where asistencia.estado = 'presente') as presentes,
        count(*) filter (where asistencia.estado = 'falta') as faltas,
        count(*) filter (where asistencia.estado = 'falta_justificada') as faltas_justificadas";

    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function delEquipo(Identificador $equipoId): ConteoDeAsistenciaAEntrenamientos
    {
        return $this->aConteo($this->deEntrenamientosRealizados()->where('entrenamiento.equipo_id', $equipoId->valor())->selectRaw(self::CONTEOS)->first());
    }

    public function deJugadoresEnEquipo(Identificador $equipoId, array $jugadores): array
    {
        $ids = array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores);
        $filas = $this->deEntrenamientosRealizados()
            ->where('entrenamiento.equipo_id', $equipoId->valor())
            ->whereIn('asistencia.jugador_id', $ids)
            ->groupBy('asistencia.jugador_id')
            ->selectRaw('asistencia.jugador_id, '.self::CONTEOS)
            ->get()
            ->keyBy('jugador_id');

        $conteos = [];
        foreach ($ids as $id) {
            $conteos[$id] = $this->aConteo($filas->get($id));
        }

        return $conteos;
    }

    public function delJugadorEnEquipos(Identificador $jugadorId, array $equipos): ConteoDeAsistenciaAEntrenamientos
    {
        return $this->aConteo($this->deEntrenamientosRealizados()
            ->where('asistencia.jugador_id', $jugadorId->valor())
            ->whereIn('entrenamiento.equipo_id', array_map(fn (Identificador $equipo) => $equipo->valor(), $equipos))
            ->selectRaw(self::CONTEOS)
            ->first());
    }

    private function deEntrenamientosRealizados(): Builder
    {
        return $this->conexion->table('asistencias_entrenamiento as asistencia')
            ->join('entrenamientos as entrenamiento', 'entrenamiento.id', '=', 'asistencia.entrenamiento_id')
            ->whereNull('asistencia.eliminado_en')
            ->where('entrenamiento.estado', EstadoDeEntrenamiento::Realizado->value);
    }

    private function aConteo(?stdClass $fila): ConteoDeAsistenciaAEntrenamientos
    {
        return new ConteoDeAsistenciaAEntrenamientos(
            (int) ($fila->presentes ?? 0),
            (int) ($fila->faltas ?? 0),
            (int) ($fila->faltas_justificadas ?? 0),
        );
    }
}
