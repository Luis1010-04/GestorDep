<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Src\Compartido\Aplicacion\MembresiasVigentes;
use Src\Compartido\Aplicacion\MembresiaVigente;
use Src\Compartido\Dominio\Identificador;

/**
 * Adaptador del puerto de Compartido para Alertas (DA-115): los jugadores no eliminados y sin baja deportiva (la misma
 * condición de `AlcanceDeJugadores`), con una fila por membresía vigente o una fila con equipo nulo si no tienen ninguna.
 * No pasa por `AlcanceDeJugadores`: el recálculo de alertas corre sin usuario de sesión.
 */
final class MembresiasVigentesPlantel implements MembresiasVigentes
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function todas(): array
    {
        return $this->filas($this->consulta());
    }

    public function delJugador(Identificador $jugador): array
    {
        return $this->filas($this->consulta()->where('jugadores.id', $jugador->valor()));
    }

    private function consulta(): Builder
    {
        return $this->conexion->table('jugadores')
            ->leftJoin('jugador_equipo as membresia', fn (JoinClause $union) => $union
                ->on('membresia.jugador_id', '=', 'jugadores.id')
                ->whereNull('membresia.hasta')
                ->whereNull('membresia.eliminado_en'))
            ->whereNull('jugadores.eliminado_en')
            ->where('jugadores.estatus_deportivo', '<>', 'baja')
            ->orderBy('jugadores.id')
            ->orderBy('membresia.equipo_id');
    }

    /** @return list<MembresiaVigente> */
    private function filas(Builder $consulta): array
    {
        return array_values($consulta
            ->get(['jugadores.id as jugador_id', 'membresia.equipo_id'])
            ->map(fn (object $fila): MembresiaVigente => new MembresiaVigente((string) $fila->jugador_id, $fila->equipo_id === null ? null : (string) $fila->equipo_id))
            ->all());
    }
}
