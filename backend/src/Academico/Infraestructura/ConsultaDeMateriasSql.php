<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Academico\Aplicacion\ConsultaDeMaterias;
use Src\Academico\Aplicacion\MateriaConDetalle;
use Src\Compartido\Dominio\Identificador;
use stdClass;

/**
 * Lee las materias de un docente con su asignatura y su periodo. Los `join` no filtran `eliminado_en`: la asignatura
 * y el periodo se muestran aunque estén eliminados (docs/hitos/f1-h2.md §6.2).
 */
final class ConsultaDeMateriasSql implements ConsultaDeMaterias
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function buscarDelDocente(Identificador $materia, Identificador $docente): ?MateriaConDetalle
    {
        $fila = $this->materiasDe($docente)->where('materias.id', $materia->valor())->first($this->columnas());

        return $fila === null ? null : $this->aDetalle($fila);
    }

    public function listarDelDocente(Identificador $docente, ?string $periodoId, int $pagina, int $porPagina): array
    {
        $materias = $this->materiasDe($docente, $periodoId)
            ->orderBy('asignaturas.clave')
            ->orderBy('materias.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get($this->columnas())
            ->map(fn (stdClass $fila) => $this->aDetalle($fila))
            ->all();

        return array_values($materias);
    }

    public function contarDelDocente(Identificador $docente, ?string $periodoId): int
    {
        return $this->materiasDe($docente, $periodoId)->count();
    }

    private function materiasDe(Identificador $docente, ?string $periodoId = null): Builder
    {
        $consulta = $this->conexion->table('materias')
            ->join('asignaturas', 'asignaturas.id', '=', 'materias.asignatura_id')
            ->join('periodos_escolares', 'periodos_escolares.id', '=', 'materias.periodo_id')
            ->whereNull('materias.eliminado_en')
            ->where('materias.docente_id', $docente->valor());

        return $periodoId === null ? $consulta : $consulta->where('materias.periodo_id', $periodoId);
    }

    /** @return list<string> */
    private function columnas(): array
    {
        return [
            'materias.id', 'materias.actualizado_en',
            'asignaturas.id as asignatura_id', 'asignaturas.clave as asignatura_clave', 'asignaturas.nombre as asignatura_nombre',
            'periodos_escolares.id as periodo_id', 'periodos_escolares.nombre as periodo_nombre',
        ];
    }

    private function aDetalle(stdClass $fila): MateriaConDetalle
    {
        return new MateriaConDetalle(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->asignatura_id),
            $fila->asignatura_clave,
            $fila->asignatura_nombre,
            Identificador::desdeTexto($fila->periodo_id),
            $fila->periodo_nombre,
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
