<?php

namespace Src\Academico\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Academico\Dominio\AmbitoDeCriterio;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\RepositorioDeCriterios;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

final class RepositorioDeCriteriosEloquent implements RepositorioDeCriterios
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function generalVigente(): CriterioDeAprobacion
    {
        $minimo = $this->conexion->table('criterios_aprobacion')
            ->where('ambito', 'general')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en')
            ->value('calificacion_minima');

        if ($minimo === null) {
            throw new LogicException('criterio_general_inexistente');
        }

        return new CriterioDeAprobacion(AmbitoDeCriterio::General, NumeroDecimal::desdeTexto((string) $minimo));
    }

    public function propiosVigentes(array $materias): array
    {
        if ($materias === []) {
            return [];
        }

        $propios = [];
        $filas = $this->conexion->table('criterios_aprobacion')
            ->where('ambito', 'materia')
            ->whereNull('vigente_hasta')
            ->whereNull('eliminado_en')
            ->whereIn('materia_id', array_map(fn (Identificador $materia): string => $materia->valor(), $materias))
            ->get(['materia_id', 'calificacion_minima']);
        foreach ($filas as $fila) {
            $propios[$fila->materia_id] = new CriterioDeAprobacion(AmbitoDeCriterio::Materia, NumeroDecimal::desdeTexto((string) $fila->calificacion_minima));
        }

        return $propios;
    }
}
