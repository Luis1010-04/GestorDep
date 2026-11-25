<?php

namespace Src\Consultas\Infraestructura;

use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;

/**
 * Qué alertas abiertas puede ver el usuario (HU-22, RN-13, RN-14; docs/hitos/f1-h6.md §5.3). Una sola definición para la
 * ruta de alertas del jugador y para el listado de estatus, para que no se desvíen:
 *
 * - **Alerta académica** (DA-89): el entrenador de un equipo del jugador y el docente con una inscripción del jugador en una
 *   materia suya del periodo vigente. Es la misma alerta para los dos.
 * - **Alertas deportivas y de sanciones** (DA-90, DA-102): los entrenadores del equipo de la alerta, sin importar cuál de
 *   ellos registró los datos con que se calculó. El entrenador de otro equipo del jugador y el docente no las ven (DD-08).
 *
 * SQL de solo lectura sobre tablas de otros contextos, como permite `docs/arquitectura.md` §4.3 a Consultas. No comprueba
 * que el jugador esté en el alcance del usuario ni fuera de baja: quien llama lo hace con `AlcanceDeJugadores`.
 */
final class VisibilidadDeAlertasSql
{
    private const TIPO_ACADEMICO = 'riesgo_baja_academica';

    private const TIPOS_DEPORTIVOS = ['riesgo_baja_deportiva', 'acumulacion_sanciones_deportivas'];

    public function __construct(
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    /** Deja en la consulta sobre `alertas` (con ese nombre o alias) solo las alertas abiertas visibles para el usuario. */
    public function restringir(ConsultaSql $consulta, string $alias = 'alertas'): ConsultaSql
    {
        $usuario = $this->usuarioActual->identificador()?->valor();
        $roles = $this->usuarioActual->roles();
        $hoy = $this->hoyDeNegocio->hoy()->format('Y-m-d');

        return $consulta->whereNull("$alias.cerrada_en")->where(function (ConsultaSql $visible) use ($alias, $usuario, $roles, $hoy): void {
            $visible->whereRaw('1 = 0');

            if ($usuario === null) {
                return;
            }

            if (in_array('entrenador', $roles, true)) {
                $visible->orWhere(fn (ConsultaSql $academica) => $academica
                    ->where("$alias.tipo", self::TIPO_ACADEMICO)
                    ->whereExists(fn (ConsultaSql $rama) => $this->entrenaAlJugador($rama, $usuario, "$alias.jugador_id")));
                $visible->orWhere(fn (ConsultaSql $deportiva) => $deportiva
                    ->whereIn("$alias.tipo", self::TIPOS_DEPORTIVOS)
                    ->whereExists(fn (ConsultaSql $rama) => $this->entrenaAlJugador($rama, $usuario, "$alias.jugador_id", "$alias.equipo_id")));
            }

            if (in_array('docente', $roles, true)) {
                $visible->orWhere(fn (ConsultaSql $academica) => $academica
                    ->where("$alias.tipo", self::TIPO_ACADEMICO)
                    ->whereExists(fn (ConsultaSql $rama) => $this->esDocenteDelJugador($rama, $usuario, "$alias.jugador_id", $hoy)));
            }
        });
    }

    /** Membresía vigente del jugador, en el equipo dado si se indica, con asignación vigente del entrenador. */
    private function entrenaAlJugador(ConsultaSql $rama, string $usuario, string $columnaJugador, ?string $columnaEquipo = null): ConsultaSql
    {
        $rama->from('jugador_equipo as membresia')
            ->join('entrenador_equipo as asignacion', 'asignacion.equipo_id', '=', 'membresia.equipo_id')
            ->whereColumn('membresia.jugador_id', $columnaJugador)
            ->whereNull('membresia.hasta')
            ->whereNull('membresia.eliminado_en')
            ->where('asignacion.usuario_id', $usuario)
            ->whereNull('asignacion.hasta')
            ->whereNull('asignacion.eliminado_en');

        if ($columnaEquipo !== null) {
            $rama->whereColumn('membresia.equipo_id', $columnaEquipo);
        }

        return $rama;
    }

    /**
     * Inscripción del jugador en una materia del docente cuyo periodo contiene la fecha de hoy de negocio (DA-89). Con alias
     * propios: una columna externa `inscripciones.jugador_id` no queda sombreada (hallazgo 5).
     */
    private function esDocenteDelJugador(ConsultaSql $rama, string $usuario, string $columnaJugador, string $hoy): ConsultaSql
    {
        return $rama->from('inscripciones as inscripcion_del_docente')
            ->join('materias as materia_del_docente', 'materia_del_docente.id', '=', 'inscripcion_del_docente.materia_id')
            ->join('periodos_escolares as periodo', 'periodo.id', '=', 'materia_del_docente.periodo_id')
            ->whereColumn('inscripcion_del_docente.jugador_id', $columnaJugador)
            ->whereNull('inscripcion_del_docente.eliminado_en')
            ->whereNull('materia_del_docente.eliminado_en')
            ->whereNull('periodo.eliminado_en')
            ->where('materia_del_docente.docente_id', $usuario)
            ->where('periodo.fecha_inicio', '<=', $hoy)
            ->where('periodo.fecha_fin', '>=', $hoy);
    }
}
