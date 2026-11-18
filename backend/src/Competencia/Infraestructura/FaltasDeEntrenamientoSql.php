<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\FaltaDeEntrenamiento;
use Src\Compartido\Aplicacion\FaltasDeEntrenamiento;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\EstadoDeEntrenamiento;

/**
 * Adaptador del puerto de Compartido para Alertas (DA-112, DA-91): las faltas, con y sin justificación, de un jugador a los
 * entrenamientos `realizado` de un equipo cuyo día, en la zona de presentación, cae entre `desde` y `hasta` inclusive.
 */
final class FaltasDeEntrenamientoSql implements FaltasDeEntrenamiento
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(Identificador $jugador, Identificador $equipo, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $zona = (string) config('app.zona_presentacion');

        return array_values($this->conexion->table('asistencias_entrenamiento as asistencia')
            ->join('entrenamientos as entrenamiento', 'entrenamiento.id', '=', 'asistencia.entrenamiento_id')
            ->where('asistencia.jugador_id', $jugador->valor())
            ->where('entrenamiento.equipo_id', $equipo->valor())
            ->where('entrenamiento.estado', EstadoDeEntrenamiento::Realizado->value)
            ->whereIn('asistencia.estado', ['falta', 'falta_justificada'])
            ->whereNull('asistencia.eliminado_en')
            ->whereNull('entrenamiento.eliminado_en')
            ->whereRaw('(entrenamiento.inicia_en at time zone ?)::date between ? and ?', [$zona, $desde->format('Y-m-d'), $hasta->format('Y-m-d')])
            ->orderBy('entrenamiento.inicia_en')
            ->get(['entrenamiento.inicia_en', 'asistencia.estado'])
            ->map(fn (object $fila): FaltaDeEntrenamiento => new FaltaDeEntrenamiento(
                (new DateTimeImmutable((string) $fila->inicia_en))->setTimezone(new DateTimeZone($zona)),
                $fila->estado === 'falta_justificada',
            ))
            ->all());
    }
}
