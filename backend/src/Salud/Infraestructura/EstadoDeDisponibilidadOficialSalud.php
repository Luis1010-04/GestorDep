<?php

namespace Src\Salud\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\EstadoDeDisponibilidadOficial;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\TipoDeEstado;

/**
 * Adaptador del puerto de Compartido, de solo lectura (docs/hitos/f1-h5.md §8, T04). Lee la fila vigente sin filtrar por
 * `eliminado_en`, igual que `RepositorioDeEstadosSql`: no hay ruta de borrado y el índice de estado vigente tampoco lo
 * considera (DA-37).
 */
final class EstadoDeDisponibilidadOficialSalud implements EstadoDeDisponibilidadOficial
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(array $jugadores): array
    {
        if ($jugadores === []) {
            return [];
        }

        $ids = array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores);
        $vigentes = $this->conexion->table('estados_disponibilidad')
            ->whereNull('vigente_hasta')
            ->whereIn('jugador_id', $ids)
            ->pluck('estado', 'jugador_id');

        $estados = [];
        foreach ($ids as $id) {
            $estados[$id] = (string) ($vigentes[$id] ?? TipoDeEstado::Apto->value);
        }

        return $estados;
    }
}
