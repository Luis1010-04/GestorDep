<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Academico\Aplicacion\AsistenciaLeida;
use Src\Academico\Aplicacion\ConsultaDeAsistencias;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Compartido\Dominio\Identificador;
use stdClass;

/** Lecturas de asistencias a clase no eliminadas. No leen jugadores. */
final class ConsultaDeAsistenciasSql implements ConsultaDeAsistencias
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function buscar(Identificador $asistenciaId): ?AsistenciaLeida
    {
        $fila = $this->vigentes()->where('id', $asistenciaId->valor())->first($this->columnas());

        return $fila === null ? null : $this->aAsistencia($fila);
    }

    public function inscripcionDe(Identificador $asistenciaId): ?Identificador
    {
        $inscripcion = $this->vigentes()->where('id', $asistenciaId->valor())->value('inscripcion_id');

        return $inscripcion === null ? null : Identificador::desdeTexto((string) $inscripcion);
    }

    public function listarDeLaInscripcion(Identificador $inscripcionId, int $pagina, int $porPagina): array
    {
        $filas = $this->vigentes()
            ->where('inscripcion_id', $inscripcionId->valor())
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get($this->columnas())
            ->map(fn (stdClass $fila): AsistenciaLeida => $this->aAsistencia($fila))
            ->all();

        return array_values($filas);
    }

    public function contarDeLaInscripcion(Identificador $inscripcionId): int
    {
        return $this->vigentes()->where('inscripcion_id', $inscripcionId->valor())->count();
    }

    private function vigentes(): Builder
    {
        return $this->conexion->table('asistencias_clase')->whereNull('eliminado_en');
    }

    /** @return list<string> */
    private function columnas(): array
    {
        return ['id', 'inscripcion_id', 'fecha', 'estado', 'actualizado_en'];
    }

    private function aAsistencia(stdClass $fila): AsistenciaLeida
    {
        return new AsistenciaLeida(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->inscripcion_id),
            new DateTimeImmutable($fila->fecha, new DateTimeZone('UTC')),
            EstadoDeAsistencia::from($fila->estado),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
