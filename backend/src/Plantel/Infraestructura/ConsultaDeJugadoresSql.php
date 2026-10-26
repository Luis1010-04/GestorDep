<?php

namespace Src\Plantel\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\ConsultaDeJugadores;
use Src\Plantel\Aplicacion\EquipoDelJugador;
use Src\Plantel\Aplicacion\JugadorConEquipos;
use stdClass;

/** Lecturas de jugadores: los filtros se aplican primero y `AlcanceDeJugadores::restringir()` va al final de las condiciones. */
final class ConsultaDeJugadoresSql implements ConsultaDeJugadores
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function listar(?Identificador $equipoId, string $campoDeOrden, bool $descendente, int $pagina, int $porPagina): array
    {
        $sentido = $descendente ? 'desc' : 'asc';
        $otroCampo = $campoDeOrden === 'apellidos' ? 'nombres' : 'apellidos';

        $filas = $this->visibles($equipoId, incluirBajas: false)
            ->orderBy("jugadores.$campoDeOrden", $sentido)
            ->orderBy("jugadores.$otroCampo", $sentido)
            ->orderBy('jugadores.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get()
            ->all();

        return $this->conEquipos(array_values($filas));
    }

    public function contar(?Identificador $equipoId): int
    {
        return $this->visibles($equipoId, incluirBajas: false)->count();
    }

    public function buscar(Identificador $jugadorId): ?JugadorConEquipos
    {
        $fila = $this->visibles(null, incluirBajas: true)->where('jugadores.id', $jugadorId->valor())->first();

        return $fila === null ? null : $this->conEquipos([$fila])[0];
    }

    private function visibles(?Identificador $equipoId, bool $incluirBajas): Builder
    {
        $consulta = $this->conexion->table('jugadores')->select('jugadores.*')->whereNull('jugadores.eliminado_en');

        if ($equipoId !== null) {
            $consulta->whereExists(
                fn (Builder $filtro) => $this->membresiasDelUsuario($filtro)
                    ->whereColumn('membresia.jugador_id', 'jugadores.id')
                    ->where('membresia.equipo_id', $equipoId->valor()),
            );
        }

        /** @var Builder */
        return $this->alcance->restringir($this->usuarioActual, $consulta, 'jugadores.id', $incluirBajas);
    }

    /** Membresías vigentes en equipos donde el usuario tiene asignación vigente. */
    private function membresiasDelUsuario(Builder $consulta): Builder
    {
        return $consulta->from('jugador_equipo as membresia')
            ->join('entrenador_equipo as asignacion', 'asignacion.equipo_id', '=', 'membresia.equipo_id')
            ->whereNull('membresia.hasta')
            ->whereNull('membresia.eliminado_en')
            ->where('asignacion.usuario_id', $this->usuarioActual->identificador()?->valor())
            ->whereNull('asignacion.hasta')
            ->whereNull('asignacion.eliminado_en');
    }

    /**
     * Una sola consulta para los equipos de todos los jugadores de la página.
     *
     * @param  list<stdClass>  $filas
     * @return list<JugadorConEquipos>
     */
    private function conEquipos(array $filas): array
    {
        if ($filas === []) {
            return [];
        }

        $equiposPorJugador = [];
        $equipos = $this->membresiasDelUsuario($this->conexion->table('jugador_equipo as membresia'))
            ->join('equipos', 'equipos.id', '=', 'membresia.equipo_id')
            ->join('categorias', 'categorias.id', '=', 'equipos.categoria_id')
            ->join('posiciones', 'posiciones.id', '=', 'membresia.posicion_id')
            ->whereIn('membresia.jugador_id', array_column($filas, 'id'))
            ->whereNull('equipos.eliminado_en')
            ->orderBy('equipos.nombre')
            ->orderBy('equipos.id')
            ->get(['membresia.jugador_id', 'equipos.id as equipo_id', 'equipos.nombre', 'categorias.id as categoria_id', 'categorias.nombre as categoria_nombre', 'posiciones.codigo as posicion']);
        foreach ($equipos as $equipo) {
            $equiposPorJugador[$equipo->jugador_id][] = new EquipoDelJugador($equipo->equipo_id, $equipo->nombre, $equipo->categoria_id, $equipo->categoria_nombre, $equipo->posicion);
        }

        return array_map(fn (stdClass $fila) => new JugadorConEquipos(
            $fila->id,
            $fila->nombres,
            $fila->apellidos,
            $fila->fecha_nacimiento,
            $fila->matricula,
            $fila->estatus_academico,
            $fila->estatus_deportivo,
            $equiposPorJugador[$fila->id] ?? [],
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        ), $filas);
    }
}
