<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/**
 * Adaptador del puerto de Compartido: membresías vigentes del equipo, restringidas por `AlcanceDeJugadores` sin bajas
 * y solo con la rama del entrenador (DA-12).
 */
final class JugadoresDelEquipoPlantel implements JugadoresDelEquipo
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function de(Identificador $equipo): array
    {
        $consulta = $this->conexion->table('jugadores')
            ->join('jugador_equipo as membresia', 'membresia.jugador_id', '=', 'jugadores.id')
            ->where('membresia.equipo_id', $equipo->valor())
            ->whereNull('membresia.hasta')
            ->whereNull('membresia.eliminado_en')
            ->whereNull('jugadores.eliminado_en')
            // El alcance deja ver a un jugador por cualquiera de sus equipos; sin esto, quien lo ve por otro equipo
            // sabría que también está en este.
            ->whereExists(fn (Builder $asignacion) => $asignacion->from('entrenador_equipo')
                ->where('entrenador_equipo.equipo_id', $equipo->valor())
                ->where('entrenador_equipo.usuario_id', $this->usuarioActual->identificador()?->valor())
                ->whereNull('entrenador_equipo.hasta')
                ->whereNull('entrenador_equipo.eliminado_en'));

        return array_values($this->alcance->restringir($this->usuarioActual, $consulta, 'jugadores.id', incluirBajas: false, soloEntrenador: true)
            ->orderBy('jugadores.apellidos')
            ->orderBy('jugadores.nombres')
            ->orderBy('jugadores.id')
            ->get(['jugadores.id', 'jugadores.nombres', 'jugadores.apellidos'])
            ->map(fn (object $fila) => new JugadorDelEquipo(Identificador::desdeTexto($fila->id), $fila->nombres, $fila->apellidos))
            ->all());
    }
}
