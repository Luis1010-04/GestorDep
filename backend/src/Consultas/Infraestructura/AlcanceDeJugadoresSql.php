<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Database\Eloquent\Builder as ConsultaEloquent;
use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;

/**
 * Visibilidad por asignacion (docs/hitos/f1-h1.md, 5.4; docs/hitos/f1-h2.md, 5.4). Cada rol aporta una rama unida con
 * OR a una base que no ve nada. La rama del docente (inscritos en sus materias) se agrega sin tocar la del entrenador.
 * Las ramas son `EXISTS` (semi-uniones): un jugador que cumple varias, o una misma rama varias veces, sale una sola vez.
 */
final class AlcanceDeJugadoresSql implements AlcanceDeJugadores
{
    public function restringir(UsuarioActual $usuario, ConsultaSql|ConsultaEloquent $consulta, string $columnaJugadorId, bool $incluirBajas = false, bool $soloEntrenador = false): ConsultaSql|ConsultaEloquent
    {
        $this->agruparCondicionesPrevias($consulta instanceof ConsultaEloquent ? $consulta->getQuery() : $consulta);

        $consulta->where(function (ConsultaSql|ConsultaEloquent $alcance) use ($usuario, $columnaJugadorId, $soloEntrenador): void {
            $alcance->whereRaw('1 = 0');

            $usuarioId = $usuario->identificador()?->valor();
            if ($usuarioId !== null && in_array('entrenador', $usuario->roles(), true)) {
                $this->ramaDelEntrenador($alcance, $usuarioId, $columnaJugadorId);
            }
            if ($usuarioId !== null && ! $soloEntrenador && in_array('docente', $usuario->roles(), true)) {
                $this->ramaDelDocente($alcance, $usuarioId, $columnaJugadorId);
            }
        });

        if (! $incluirBajas) {
            $consulta->whereNotExists(
                fn (ConsultaSql $baja) => $baja->from('jugadores as jugador_en_baja')
                    ->whereColumn('jugador_en_baja.id', $columnaJugadorId)
                    ->where('jugador_en_baja.estatus_deportivo', 'baja'),
            );
        }

        return $consulta;
    }

    /**
     * Membresia vigente en un equipo con asignacion vigente del entrenador.
     *
     * @param  ConsultaSql|ConsultaEloquent<*>  $alcance
     */
    private function ramaDelEntrenador(ConsultaSql|ConsultaEloquent $alcance, string $usuarioId, string $columnaJugadorId): void
    {
        $alcance->orWhereExists(
            fn (ConsultaSql $rama) => $rama->from('jugador_equipo as membresia')
                ->join('entrenador_equipo as asignacion', 'asignacion.equipo_id', '=', 'membresia.equipo_id')
                ->whereColumn('membresia.jugador_id', $columnaJugadorId)
                ->whereNull('membresia.hasta')
                ->whereNull('membresia.eliminado_en')
                ->where('asignacion.usuario_id', $usuarioId)
                ->whereNull('asignacion.hasta')
                ->whereNull('asignacion.eliminado_en'),
        );
    }

    /**
     * Inscripcion no eliminada en una materia no eliminada de la que el usuario es docente, en cualquier periodo (RN-20).
     *
     * @param  ConsultaSql|ConsultaEloquent<*>  $alcance
     */
    private function ramaDelDocente(ConsultaSql|ConsultaEloquent $alcance, string $usuarioId, string $columnaJugadorId): void
    {
        $alcance->orWhereExists(
            fn (ConsultaSql $rama) => $rama->from('inscripciones')
                ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
                ->whereColumn('inscripciones.jugador_id', $columnaJugadorId)
                ->whereNull('inscripciones.eliminado_en')
                ->whereNull('materias.eliminado_en')
                ->where('materias.docente_id', $usuarioId),
        );
    }

    /**
     * Un `orWhere` previo de quien llama (`a OR b`) dejaria el alcance subordinado (`a OR (b AND alcance)`).
     * Se encierran las condiciones previas en un grupo para que el alcance las acompane con AND.
     */
    private function agruparCondicionesPrevias(ConsultaSql $consulta): void
    {
        $hayOr = false;
        foreach ($consulta->wheres as $condicion) {
            $hayOr = $hayOr || ($condicion['boolean'] ?? 'and') === 'or';
        }
        if (! $hayOr) {
            return;
        }

        $grupo = $consulta->forNestedWhere();
        $grupo->wheres = $consulta->wheres;
        $grupo->setBindings($consulta->getRawBindings()['where'], 'where');

        $consulta->wheres = [];
        $consulta->setBindings([], 'where');
        $consulta->addNestedWhereQuery($grupo);
    }
}
