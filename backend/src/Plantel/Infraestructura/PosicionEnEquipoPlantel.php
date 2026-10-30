<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Dominio\Identificador;

/** Adaptador del puerto de Compartido: `posicion_id` de la membresía vigente y no eliminada de `jugador_equipo` (DD-05). */
final class PosicionEnEquipoPlantel implements PosicionEnEquipo
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(Identificador $jugador, Identificador $equipo): ?Identificador
    {
        $posicion = $this->conexion->table('jugador_equipo')
            ->where('jugador_id', $jugador->valor())
            ->where('equipo_id', $equipo->valor())
            ->whereNull('hasta')
            ->whereNull('eliminado_en')
            ->value('posicion_id');

        return is_string($posicion) ? Identificador::desdeTexto($posicion) : null;
    }
}
