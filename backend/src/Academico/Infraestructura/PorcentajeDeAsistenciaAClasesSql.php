<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Academico\Dominio\ConteoDeAsistencia;
use Src\Compartido\Aplicacion\AsistenciaPorMes;
use Src\Compartido\Aplicacion\PorcentajeDeAsistenciaAClases;
use Src\Compartido\Dominio\Identificador;

/**
 * Cuenta por mes los registros de `asistencias_clase` de la inscripción del jugador en la materia y deja el porcentaje a la
 * regla de Académico, `ConteoDeAsistencia` (RN-05). El mes es el de la fecha de la clase, que ya es una fecha de calendario.
 */
final class PorcentajeDeAsistenciaAClasesSql implements PorcentajeDeAsistenciaAClases
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function delJugadorEnLaMateria(Identificador $jugador, Identificador $materia): AsistenciaPorMes
    {
        $filas = $this->conexion->table('asistencias_clase as asistencia')
            ->join('inscripciones as inscripcion', 'inscripcion.id', '=', 'asistencia.inscripcion_id')
            ->whereNull('asistencia.eliminado_en')
            ->whereNull('inscripcion.eliminado_en')
            ->where('inscripcion.jugador_id', $jugador->valor())
            ->where('inscripcion.materia_id', $materia->valor())
            ->groupBy('mes')
            ->orderBy('mes')
            ->selectRaw("to_char(asistencia.fecha, 'YYYY-MM') as mes,
                count(*) filter (where asistencia.estado = 'presente') as presentes,
                count(*) filter (where asistencia.estado = 'falta_justificada') as faltas_justificadas,
                count(*) filter (where asistencia.estado = 'falta') as faltas,
                max(asistencia.actualizado_en) as actualizado_en")
            ->get();

        $porcentajes = [];
        $actualizadoEn = null;
        foreach ($filas as $fila) {
            $porcentaje = (new ConteoDeAsistencia((int) $fila->presentes, (int) $fila->faltas_justificadas, (int) $fila->faltas))->porcentaje();
            if ($porcentaje !== null) {
                $porcentajes[(string) $fila->mes] = $porcentaje;
            }

            $momento = (new DateTimeImmutable((string) $fila->actualizado_en))->setTimezone(new DateTimeZone('UTC'));
            $actualizadoEn = $actualizadoEn === null || $momento > $actualizadoEn ? $momento : $actualizadoEn;
        }

        return new AsistenciaPorMes($porcentajes, $actualizadoEn);
    }
}
