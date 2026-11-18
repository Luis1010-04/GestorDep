<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\TarjetasDeUnRegistro;
use Src\Compartido\Aplicacion\TarjetasPorPartido;
use Src\Compartido\Dominio\Identificador;

/**
 * Adaptador del puerto de Compartido para Alertas (DA-112): las tarjetas de las filas de `rendimientos_partido` no
 * eliminadas de un jugador en un equipo, de cualquier entrenador del equipo (DA-102).
 */
final class TarjetasPorPartidoSql implements TarjetasPorPartido
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(Identificador $jugador, Identificador $equipo): array
    {
        return array_values($this->conexion->table('rendimientos_partido')
            ->where('jugador_id', $jugador->valor())
            ->where('equipo_id', $equipo->valor())
            ->whereNull('eliminado_en')
            ->orderBy('partido_id')
            ->orderBy('registrado_por')
            ->get(['partido_id', 'tarjetas_amarillas', 'tarjetas_rojas'])
            ->map(fn (object $fila): TarjetasDeUnRegistro => new TarjetasDeUnRegistro((string) $fila->partido_id, (int) $fila->tarjetas_amarillas, (int) $fila->tarjetas_rojas))
            ->all());
    }
}
