<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Academico\Aplicacion\FuenteDeDatosAcademicos;
use Src\Academico\Dominio\CalificacionDeMateria;
use Src\Academico\Dominio\ConteoDeAsistencia;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\MateriaEvaluada;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use stdClass;

/** Lee `inscripciones`, `calificaciones`, `asistencias_clase` y `criterios_aprobacion`; no filtra por jugador visible: es cálculo interno. */
final class FuenteDeDatosAcademicosSql implements FuenteDeDatosAcademicos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly RepositorioDeCriterios $criterios,
    ) {}

    public function materiasEvaluadas(Identificador $jugador, Identificador $periodo): array
    {
        $inscripciones = $this->inscripcionesDelPeriodo($jugador, $periodo)->get(['inscripciones.id as inscripcion_id', 'inscripciones.materia_id'])->all();
        if ($inscripciones === []) {
            return [];
        }

        $propios = $this->criterios->propiosVigentes(array_values(array_map(fn (stdClass $fila): Identificador => Identificador::desdeTexto($fila->materia_id), $inscripciones)));
        $general = $this->criterios->generalVigente();

        $finales = [];
        $parciales = [];
        $calificaciones = $this->conexion->table('calificaciones')
            ->whereNull('eliminado_en')
            ->whereIn('inscripcion_id', array_column($inscripciones, 'inscripcion_id'))
            ->get(['inscripcion_id', 'tipo', 'valor']);
        foreach ($calificaciones as $calificacion) {
            $valor = NumeroDecimal::desdeTexto((string) $calificacion->valor);
            if ($calificacion->tipo === 'final') {
                $finales[$calificacion->inscripcion_id] = $valor;
            } else {
                $parciales[$calificacion->inscripcion_id][] = $valor;
            }
        }

        return array_values(array_map(
            fn (stdClass $fila): MateriaEvaluada => new MateriaEvaluada(
                CalificacionDeMateria::calcular($finales[$fila->inscripcion_id] ?? null, $parciales[$fila->inscripcion_id] ?? []),
                CriterioDeAprobacion::resolver($propios[$fila->materia_id] ?? null, $general),
            ),
            $inscripciones,
        ));
    }

    public function conteoDeAsistencia(Identificador $jugador, Identificador $periodo): ConteoDeAsistencia
    {
        $conteos = $this->conexion->table('asistencias_clase')
            ->whereNull('eliminado_en')
            ->whereIn('inscripcion_id', $this->inscripcionesDelPeriodo($jugador, $periodo)->select('inscripciones.id'))
            ->groupBy('estado')
            ->selectRaw('estado, count(*) as total')
            ->pluck('total', 'estado');

        return new ConteoDeAsistencia((int) ($conteos['presente'] ?? 0), (int) ($conteos['falta_justificada'] ?? 0), (int) ($conteos['falta'] ?? 0));
    }

    public function jugadoresDeLaMateria(Identificador $materia): array
    {
        return $this->identificadores(
            $this->inscripcionesVigentes()->where('inscripciones.materia_id', $materia->valor()),
        );
    }

    public function jugadoresConInscripciones(): array
    {
        return $this->identificadores($this->inscripcionesVigentes());
    }

    private function inscripcionesVigentes(): Builder
    {
        return $this->conexion->table('inscripciones')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
            ->whereNull('inscripciones.eliminado_en')
            ->whereNull('materias.eliminado_en');
    }

    private function inscripcionesDelPeriodo(Identificador $jugador, Identificador $periodo): Builder
    {
        return $this->inscripcionesVigentes()
            ->where('inscripciones.jugador_id', $jugador->valor())
            ->where('materias.periodo_id', $periodo->valor());
    }

    /** @return list<Identificador> */
    private function identificadores(Builder $consulta): array
    {
        return array_values($consulta->distinct()->orderBy('inscripciones.jugador_id')->pluck('inscripciones.jugador_id')
            ->map(fn (string $id): Identificador => Identificador::desdeTexto($id))
            ->all());
    }
}
