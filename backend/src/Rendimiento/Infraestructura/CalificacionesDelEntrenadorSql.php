<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\InicioDePartidos;
use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Aplicacion\CalificacionesDelEntrenador;
use stdClass;

/**
 * Lee `rendimientos_partido` y ordena con la fecha de inicio que da `InicioDePartidos`, sin leer tablas de Competencia
 * (v1.0.5). Un rendimiento cuyo partido ya no existe o está eliminado no cuenta (DA-34).
 */
final class CalificacionesDelEntrenadorSql implements CalificacionesDelEntrenador
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly InicioDePartidos $inicios,
    ) {}

    public function de(Identificador $jugador, Identificador $equipo, Identificador $entrenador): array
    {
        $filas = $this->conexion->table('rendimientos_partido')
            ->whereNull('eliminado_en')
            ->where('jugador_id', $jugador->valor())
            ->where('equipo_id', $equipo->valor())
            ->where('registrado_por', $entrenador->valor())
            ->get(['id', 'partido_id', 'calificacion_general'])
            ->all();

        $inicios = $this->inicios->de(array_values(array_map(
            fn (string $partido) => Identificador::desdeTexto($partido),
            array_unique(array_map(fn (stdClass $fila) => (string) $fila->partido_id, $filas)),
        )));
        $conFecha = array_values(array_filter($filas, fn (stdClass $fila) => isset($inicios[$fila->partido_id])));
        usort($conFecha, fn (stdClass $a, stdClass $b) => [$inicios[$b->partido_id], $b->id] <=> [$inicios[$a->partido_id], $a->id]);

        return array_map(fn (stdClass $fila) => (int) $fila->calificacion_general, $conFecha);
    }
}
