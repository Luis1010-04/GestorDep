<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido: asignaciones vigentes de `entrenador_equipo` de un usuario con rol entrenador (DA-13). */
final class EquiposDelEntrenadorPlantel implements EquiposDelEntrenador
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function equiposDe(Identificador $usuario): array
    {
        return array_values($this->asignaciones($usuario)
            ->orderBy('asignacion.equipo_id')
            ->pluck('asignacion.equipo_id')
            ->map(fn (string $equipo) => Identificador::desdeTexto($equipo))
            ->all());
    }

    public function entrena(Identificador $usuario, Identificador $equipo): bool
    {
        return $this->asignaciones($usuario)->where('asignacion.equipo_id', $equipo->valor())->exists();
    }

    private function asignaciones(Identificador $usuario): Builder
    {
        return $this->conexion->table('entrenador_equipo as asignacion')
            ->join('equipos', 'equipos.id', '=', 'asignacion.equipo_id')
            ->join('usuarios', 'usuarios.id', '=', 'asignacion.usuario_id')
            ->where('asignacion.usuario_id', $usuario->valor())
            ->whereNull('asignacion.hasta')
            ->whereNull('asignacion.eliminado_en')
            ->whereNull('equipos.eliminado_en')
            ->whereNull('usuarios.eliminado_en')
            ->whereExists(fn (Builder $rol) => $rol->from('usuario_rol')
                ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
                ->whereColumn('usuario_rol.usuario_id', 'asignacion.usuario_id')
                ->where('roles.codigo', 'entrenador'));
    }
}
