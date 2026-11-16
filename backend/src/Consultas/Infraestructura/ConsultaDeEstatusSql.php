<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultaDeAlertas;
use Src\Consultas\Aplicacion\ConsultaDeEstatus;
use Src\Consultas\Aplicacion\FilaDeEstatus;
use Src\Consultas\Aplicacion\FiltrosDeEstatus;
use stdClass;

/**
 * SQL de solo lectura sobre `jugadores`, `jugador_equipo`, `entrenador_equipo`, `inscripciones`, `materias`, `avisos_lesion`
 * y `alertas` (§4.3). Los jugadores salen de `AlcanceDeJugadores`, sin bajas (RN-23). Los filtros por equipo y por materia
 * solo aciertan con equipos que el usuario entrena y materias que da: un id ajeno da una lista vacía y no revela nada (P-05).
 */
final class ConsultaDeEstatusSql implements ConsultaDeEstatus
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
        private readonly VisibilidadDeAlertasSql $visibilidad,
        private readonly ConsultaDeAlertas $alertas,
    ) {}

    public function pagina(FiltrosDeEstatus $filtros, int $pagina, int $porPagina): array
    {
        $usuario = $this->usuarioActual->identificador();
        if ($usuario === null) {
            return ['elementos' => [], 'total' => 0];
        }

        $jugadores = $this->jugadoresDelUsuario($filtros, $usuario);
        $total = (clone $jugadores)->count();

        $filas = $jugadores
            ->orderBy('jugadores.apellidos')
            ->orderBy('jugadores.nombres')
            ->orderBy('jugadores.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->select(['jugadores.id', 'jugadores.nombres', 'jugadores.apellidos', 'jugadores.matricula'])
            ->selectRaw($this->avisoPendiente(), [$usuario->valor(), $usuario->valor()])
            ->get();

        $alertas = $this->alertas->deLosJugadores(array_values(array_map(fn (stdClass $fila): string => (string) $fila->id, $filas->all())));

        return [
            'elementos' => array_values(array_map(fn (stdClass $fila): FilaDeEstatus => new FilaDeEstatus(
                Identificador::desdeTexto($fila->id),
                $fila->nombres,
                $fila->apellidos,
                $fila->matricula,
                (bool) $fila->aviso_pendiente,
                $alertas[$fila->id] ?? [],
            ), $filas->all())),
            'total' => $total,
        ];
    }

    public function jugadorVisible(Identificador $jugador): bool
    {
        return $this->alcance->restringir(
            $this->usuarioActual,
            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en')->where('jugadores.id', $jugador->valor()),
            'jugadores.id',
        )->exists();
    }

    private function jugadoresDelUsuario(FiltrosDeEstatus $filtros, Identificador $usuario): ConsultaSql
    {
        $jugadores = $this->alcance->restringir(
            $this->usuarioActual,
            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),
            'jugadores.id',
        );

        if ($filtros->equipoId !== null) {
            $jugadores->whereExists(fn (ConsultaSql $membresia) => $membresia->from('jugador_equipo as membresia_filtrada')
                ->join('entrenador_equipo as asignacion_filtrada', 'asignacion_filtrada.equipo_id', '=', 'membresia_filtrada.equipo_id')
                ->whereColumn('membresia_filtrada.jugador_id', 'jugadores.id')
                ->where('membresia_filtrada.equipo_id', $filtros->equipoId->valor())
                ->whereNull('membresia_filtrada.hasta')
                ->whereNull('membresia_filtrada.eliminado_en')
                ->where('asignacion_filtrada.usuario_id', $usuario->valor())
                ->whereNull('asignacion_filtrada.hasta')
                ->whereNull('asignacion_filtrada.eliminado_en'));
        }

        if ($filtros->materiaId !== null) {
            $jugadores->whereExists(fn (ConsultaSql $inscripcion) => $inscripcion->from('inscripciones as inscripcion_filtrada')
                ->join('materias as materia_filtrada', 'materia_filtrada.id', '=', 'inscripcion_filtrada.materia_id')
                ->whereColumn('inscripcion_filtrada.jugador_id', 'jugadores.id')
                ->where('inscripcion_filtrada.materia_id', $filtros->materiaId->valor())
                ->whereNull('inscripcion_filtrada.eliminado_en')
                ->whereNull('materia_filtrada.eliminado_en')
                ->where('materia_filtrada.docente_id', $usuario->valor()));
        }

        if ($filtros->tipoDeAlerta !== null) {
            $jugadores->whereExists(fn (ConsultaSql $alerta) => $this->visibilidad->restringir(
                $alerta->from('alertas as alerta_filtrada')
                    ->whereColumn('alerta_filtrada.jugador_id', 'jugadores.id')
                    ->where('alerta_filtrada.tipo', $filtros->tipoDeAlerta),
                'alerta_filtrada',
            ));
        }

        return $jugadores;
    }

    /**
     * Un aviso pendiente cuenta para los entrenadores del jugador y para quien lo creó (RN-16, DA-108): el docente solo
     * ve el indicador de los avisos que él creó. Los parámetros son el usuario, dos veces.
     *
     * @return literal-string
     */
    private function avisoPendiente(): string
    {
        return <<<'SQL'
            exists (
                select 1 from avisos_lesion as aviso
                where aviso.jugador_id = jugadores.id
                    and aviso.estado = 'pendiente'
                    and aviso.eliminado_en is null
                    and (
                        aviso.creado_por = ?
                        or exists (
                            select 1 from jugador_equipo as membresia
                            join entrenador_equipo as asignacion on asignacion.equipo_id = membresia.equipo_id
                            where membresia.jugador_id = jugadores.id
                                and membresia.hasta is null and membresia.eliminado_en is null
                                and asignacion.usuario_id = ?
                                and asignacion.hasta is null and asignacion.eliminado_en is null
                        )
                    )
            ) as aviso_pendiente
            SQL;
    }
}
