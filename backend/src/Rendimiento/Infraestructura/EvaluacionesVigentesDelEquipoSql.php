<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\EvaluacionesVigentesDelEquipo;
use Src\Compartido\Aplicacion\EvaluacionVigente;
use Src\Compartido\Dominio\Identificador;

/**
 * Adaptador del puerto de Compartido para Alertas (DA-112, DA-92): la última fila de `evaluaciones_rendimiento` de cada
 * entrenador sobre el jugador en el equipo, la más reciente por `calculado_en`.
 */
final class EvaluacionesVigentesDelEquipoSql implements EvaluacionesVigentesDelEquipo
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(Identificador $jugador, Identificador $equipo): array
    {
        /** @var list<object{entrenador_id: string, resultado: string, promedio: string|null}> $filas */
        $filas = $this->conexion->select(
            'select distinct on (entrenador_id) entrenador_id, resultado, promedio
            from evaluaciones_rendimiento
            where jugador_id = ? and equipo_id = ?
            order by entrenador_id, calculado_en desc, id desc',
            [$jugador->valor(), $equipo->valor()],
        );

        return array_map(
            fn (object $fila): EvaluacionVigente => new EvaluacionVigente((string) $fila->entrenador_id, (string) $fila->resultado, $fila->promedio === null ? null : (string) $fila->promedio),
            $filas,
        );
    }
}
