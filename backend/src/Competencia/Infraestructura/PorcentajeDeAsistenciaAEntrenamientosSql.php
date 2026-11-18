<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AsistenciaPorMes;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAEntrenamientos;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;

/**
 * Cuenta por mes los registros de asistencia de entrenamientos `realizado` y deja el porcentaje a la regla de Competencia,
 * `ConteoDeAsistenciaAEntrenamientos` (DD-51). El mes es el de la zona de presentación (`app.zona_presentacion`): un
 * entrenamiento a las 23:30 del 31 de octubre en México es de octubre, aunque en UTC ya sea noviembre.
 */
final class PorcentajeDeAsistenciaAEntrenamientosSql implements PorcentajeDeAsistenciaAEntrenamientos
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function delJugadorEnEquipos(Identificador $jugador, array $equipos): AsistenciaPorMes
    {
        if ($equipos === []) {
            return new AsistenciaPorMes([], null);
        }

        return $this->porMes($this->deEntrenamientosRealizados()
            ->where('asistencia.jugador_id', $jugador->valor())
            ->whereIn('entrenamiento.equipo_id', array_map(fn (Identificador $equipo): string => $equipo->valor(), $equipos)));
    }

    public function delEquipo(Identificador $equipo): AsistenciaPorMes
    {
        return $this->porMes($this->deEntrenamientosRealizados()->where('entrenamiento.equipo_id', $equipo->valor()));
    }

    private function deEntrenamientosRealizados(): Builder
    {
        return $this->conexion->table('asistencias_entrenamiento as asistencia')
            ->join('entrenamientos as entrenamiento', 'entrenamiento.id', '=', 'asistencia.entrenamiento_id')
            ->whereNull('asistencia.eliminado_en')
            ->whereNull('entrenamiento.eliminado_en')
            ->where('entrenamiento.estado', EstadoDeEntrenamiento::Realizado->value);
    }

    private function porMes(Builder $registros): AsistenciaPorMes
    {
        $filas = $registros
            ->groupBy('mes')
            ->orderBy('mes')
            ->selectRaw("to_char(entrenamiento.inicia_en at time zone ?, 'YYYY-MM') as mes,
                count(*) filter (where asistencia.estado = 'presente') as presentes,
                count(*) filter (where asistencia.estado = 'falta') as faltas,
                count(*) filter (where asistencia.estado = 'falta_justificada') as faltas_justificadas,
                max(asistencia.actualizado_en) as actualizado_en", [(string) config('app.zona_presentacion')])
            ->get();

        $porcentajes = [];
        $actualizadoEn = null;
        foreach ($filas as $fila) {
            $porcentaje = (new ConteoDeAsistenciaAEntrenamientos((int) $fila->presentes, (int) $fila->faltas, (int) $fila->faltas_justificadas))->porcentaje();
            if ($porcentaje !== null) {
                $porcentajes[(string) $fila->mes] = $porcentaje;
            }

            $momento = (new DateTimeImmutable((string) $fila->actualizado_en))->setTimezone(new DateTimeZone('UTC'));
            $actualizadoEn = $actualizadoEn === null || $momento > $actualizadoEn ? $momento : $actualizadoEn;
        }

        return new AsistenciaPorMes($porcentajes, $actualizadoEn);
    }
}
