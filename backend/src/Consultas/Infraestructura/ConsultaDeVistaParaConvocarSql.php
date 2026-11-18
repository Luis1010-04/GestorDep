<?php

namespace Src\Consultas\Infraestructura;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Consultas\Aplicacion\ConsultaDeVistaParaConvocar;
use Src\Consultas\Aplicacion\ConvocatoriaDelPartido;
use Src\Consultas\Aplicacion\DatosDeAsistencia;
use Src\Consultas\Aplicacion\DatosDeDisponibilidad;
use Src\Consultas\Aplicacion\DatosDeEstadoFisico;
use Src\Consultas\Aplicacion\DatosDeSituacionAcademica;
use Src\Consultas\Aplicacion\EvaluacionReciente;
use Src\Consultas\Aplicacion\JugadorConvocable;
use Src\Consultas\Aplicacion\PorcentajeDeAsistencia;
use stdClass;

/**
 * SQL de solo lectura sobre `jugadores`, `jugador_equipo`, `entrenador_equipo`, `evaluaciones_rendimiento` (DA-120),
 * `estados_disponibilidad`, `avisos_lesion`, `periodos_escolares`, `resumenes_academicos`, `asistencias_entrenamiento`,
 * `entrenamientos`, `mediciones_corporales`, `pruebas_fisicas` y `tipos_prueba` (§4.3). Los jugadores salen de
 * `AlcanceDeJugadores`, con la rama del entrenador y sin bajas (RN-23). Lo que registra un entrenador sale solo de lo que
 * registró el usuario (RN-14).
 */
final class ConsultaDeVistaParaConvocarSql implements ConsultaDeVistaParaConvocar
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function jugador(Identificador $jugador): ?JugadorConvocable
    {
        $fila = $this->visibles()->where('jugadores.id', $jugador->valor())->first(self::COLUMNAS_DEL_JUGADOR);

        return $fila === null ? null : self::jugadorConvocable($fila);
    }

    public function jugadorEnEquipoDelUsuario(Identificador $jugador, Identificador $equipo): bool
    {
        return $this->entrena($equipo)
            && $this->visibles()->where('jugadores.id', $jugador->valor())->whereExists($this->membresiaVigente($equipo))->exists();
    }

    public function jugadoresDelEquipo(Identificador $equipo): ?array
    {
        if (! $this->entrena($equipo)) {
            return null;
        }

        return array_values($this->visibles()
            ->whereExists($this->membresiaVigente($equipo))
            ->orderBy('jugadores.apellidos')
            ->orderBy('jugadores.nombres')
            ->orderBy('jugadores.id')
            ->get(self::COLUMNAS_DEL_JUGADOR)
            ->map(fn (stdClass $fila): JugadorConvocable => self::jugadorConvocable($fila))
            ->all());
    }

    public function convocatoriaDelPartido(Identificador $partido, int $pagina, int $porPagina): ?ConvocatoriaDelPartido
    {
        $equipo = $this->conexion->table('partidos')->where('id', $partido->valor())->whereNull('eliminado_en')->value('equipo_id');
        $equipoDelPartido = $equipo === null ? null : Identificador::desdeTexto((string) $equipo);
        if ($equipoDelPartido === null || ! $this->entrena($equipoDelPartido)) {
            return null;
        }

        // Como `ConsultaDeConvocatoriaSql` de H3: el total y la página salen de la misma consulta restringida por alcance.
        $convocados = $this->alcance->restringir(
            $this->usuarioActual,
            $this->conexion->table('convocatorias')
                ->join('jugadores', 'jugadores.id', '=', 'convocatorias.jugador_id')
                ->where('convocatorias.partido_id', $partido->valor())
                ->whereNull('convocatorias.eliminado_en')
                ->whereNull('jugadores.eliminado_en'),
            'convocatorias.jugador_id',
            soloEntrenador: true,
        );
        $total = (clone $convocados)->count();
        $filas = $convocados
            ->orderBy('convocatorias.creado_en')
            ->orderBy('convocatorias.id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(self::COLUMNAS_DEL_JUGADOR);

        return new ConvocatoriaDelPartido(
            $equipoDelPartido,
            array_values($filas->map(fn (stdClass $fila): JugadorConvocable => self::jugadorConvocable($fila))->all()),
            $total,
        );
    }

    public function equipoDeLaUltimaEvaluacion(Identificador $jugador): ?Identificador
    {
        $equipo = $this->evaluacionesDelUsuario($jugador)->value('equipo_id');

        return $equipo === null ? null : Identificador::desdeTexto((string) $equipo);
    }

    public function evaluacionReciente(Identificador $jugador, Identificador $equipo): ?EvaluacionReciente
    {
        $fila = $this->evaluacionesDelUsuario($jugador)->where('equipo_id', $equipo->valor())->first(['promedio', 'resultado', 'calculado_en']);

        return $fila === null ? null : new EvaluacionReciente(
            $fila->promedio === null ? null : NumeroDecimal::desdeTexto((string) $fila->promedio),
            (string) $fila->resultado,
            self::instante((string) $fila->calculado_en),
        );
    }

    public function disponibilidad(Identificador $jugador): DatosDeDisponibilidad
    {
        $estado = $this->conexion->table('estados_disponibilidad')
            ->where('jugador_id', $jugador->valor())
            ->whereNull('vigente_hasta')
            ->first(['estado', 'fecha_estimada_regreso', 'actualizado_en']);
        $avisoPendiente = $this->conexion->table('avisos_lesion')
            ->where('jugador_id', $jugador->valor())
            ->where('estado', 'pendiente')
            ->whereNull('eliminado_en')
            ->exists();

        return new DatosDeDisponibilidad(
            $estado === null ? 'apto' : (string) $estado->estado,
            $estado === null || $estado->fecha_estimada_regreso === null ? null : (string) $estado->fecha_estimada_regreso,
            $avisoPendiente,
            $estado === null ? null : self::instante((string) $estado->actualizado_en),
        );
    }

    public function situacionAcademica(Identificador $jugador): DatosDeSituacionAcademica
    {
        $hoy = $this->hoyDeNegocio->hoy()->format('Y-m-d');
        $periodo = $this->conexion->table('periodos_escolares')
            ->whereNull('eliminado_en')
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->first(['id', 'nombre']);

        if ($periodo === null) {
            return new DatosDeSituacionAcademica(null, null, 0, 0, true, null, null, null);
        }

        $resumen = $this->conexion->table('resumenes_academicos')
            ->where('jugador_id', $jugador->valor())
            ->where('periodo_id', $periodo->id)
            ->first(['promedio', 'materias_reprobadas', 'materias_sin_calificar', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en']);
        $datosDelPeriodo = ['id' => (string) $periodo->id, 'nombre' => (string) $periodo->nombre];

        if ($resumen === null) {
            return new DatosDeSituacionAcademica($datosDelPeriodo, null, 0, 0, true, null, null, null);
        }

        return new DatosDeSituacionAcademica(
            $datosDelPeriodo,
            self::decimal($resumen->promedio),
            (int) $resumen->materias_reprobadas,
            (int) $resumen->materias_sin_calificar,
            $resumen->promedio === null || (int) $resumen->materias_sin_calificar > 0,
            self::decimal($resumen->porcentaje_asistencia),
            $resumen->nivel_riesgo === null ? null : (string) $resumen->nivel_riesgo,
            self::instante((string) $resumen->calculado_en),
        );
    }

    public function asistencia(Identificador $jugador): DatosDeAsistencia
    {
        $usuario = $this->usuario();
        $deLosEquipos = $this->conexion->table('asistencias_entrenamiento as asistencia')
            ->join('entrenamientos as entrenamiento', 'entrenamiento.id', '=', 'asistencia.entrenamiento_id')
            ->where('asistencia.jugador_id', $jugador->valor())
            ->where('entrenamiento.estado', 'realizado')
            ->whereNull('asistencia.eliminado_en')
            ->whereNull('entrenamiento.eliminado_en')
            ->whereExists(fn (Builder $asignacion) => $asignacion->from('entrenador_equipo as asignacion')
                ->whereColumn('asignacion.equipo_id', 'entrenamiento.equipo_id')
                ->where('asignacion.usuario_id', $usuario->valor())
                ->whereNull('asignacion.hasta')
                ->whereNull('asignacion.eliminado_en'));

        return new DatosDeAsistencia(
            $this->porcentaje(clone $deLosEquipos),
            $this->porcentaje($deLosEquipos->where('asistencia.creado_por', $usuario->valor())),
        );
    }

    public function estadoFisico(Identificador $jugador): DatosDeEstadoFisico
    {
        $usuario = $this->usuario()->valor();
        $medicion = $this->conexion->table('mediciones_corporales')
            ->where('jugador_id', $jugador->valor())
            ->where('registrado_por', $usuario)
            ->whereNull('eliminado_en')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->first(['id', 'equipo_id', 'fecha', 'periodo', 'peso_kg', 'estatura_cm', 'imc', 'actualizado_en']);
        $pruebas = $this->conexion->table('pruebas_fisicas as prueba')
            ->join('tipos_prueba as tipo', 'tipo.id', '=', 'prueba.tipo_prueba_id')
            ->where('prueba.jugador_id', $jugador->valor())
            ->where('prueba.registrado_por', $usuario)
            ->whereNull('prueba.eliminado_en')
            ->whereRaw('prueba.id = (select ultima.id from pruebas_fisicas as ultima where ultima.jugador_id = prueba.jugador_id and ultima.registrado_por = prueba.registrado_por and ultima.tipo_prueba_id = prueba.tipo_prueba_id and ultima.eliminado_en is null order by ultima.fecha desc, ultima.id desc limit 1)')
            ->orderBy('tipo.codigo')
            ->get(['prueba.id', 'prueba.equipo_id', 'prueba.tipo_prueba_id', 'prueba.fecha', 'prueba.periodo', 'prueba.resultado', 'prueba.actualizado_en']);

        $instantes = array_map(fn (stdClass $fila): DateTimeImmutable => self::instante((string) $fila->actualizado_en), array_values(array_filter([$medicion, ...$pruebas->all()])));
        $actualizadoEn = $instantes === [] ? null : max($instantes);

        return new DatosDeEstadoFisico(
            $medicion === null ? null : [
                'id' => (string) $medicion->id,
                'equipo_id' => (string) $medicion->equipo_id,
                'fecha' => (string) $medicion->fecha,
                'periodo' => (string) $medicion->periodo,
                'peso_kg' => NumeroDecimal::desdeTexto((string) $medicion->peso_kg)->comoCadena(2),
                'estatura_cm' => NumeroDecimal::desdeTexto((string) $medicion->estatura_cm)->comoCadena(1),
                'imc' => NumeroDecimal::desdeTexto((string) $medicion->imc)->comoCadena(2),
                'actualizado_en' => self::instante((string) $medicion->actualizado_en)->format(DateTimeInterface::ATOM),
            ],
            array_values($pruebas->map(fn (stdClass $prueba): array => [
                'id' => (string) $prueba->id,
                'equipo_id' => (string) $prueba->equipo_id,
                'tipo_prueba_id' => (string) $prueba->tipo_prueba_id,
                'fecha' => (string) $prueba->fecha,
                'periodo' => (string) $prueba->periodo,
                'resultado' => NumeroDecimal::desdeTexto((string) $prueba->resultado)->comoCadena(2),
                'actualizado_en' => self::instante((string) $prueba->actualizado_en)->format(DateTimeInterface::ATOM),
            ])->all()),
            $actualizadoEn,
        );
    }

    private const COLUMNAS_DEL_JUGADOR = ['jugadores.id', 'jugadores.nombres', 'jugadores.apellidos', 'jugadores.matricula'];

    /** Los jugadores no eliminados del alcance del usuario como entrenador, sin bajas (RN-23). */
    private function visibles(): Builder
    {
        return $this->alcance->restringir(
            $this->usuarioActual,
            $this->conexion->table('jugadores')->whereNull('jugadores.eliminado_en'),
            'jugadores.id',
            soloEntrenador: true,
        );
    }

    private function membresiaVigente(Identificador $equipo): Closure
    {
        return fn (Builder $membresia) => $membresia->from('jugador_equipo as membresia')
            ->whereColumn('membresia.jugador_id', 'jugadores.id')
            ->where('membresia.equipo_id', $equipo->valor())
            ->whereNull('membresia.hasta')
            ->whereNull('membresia.eliminado_en');
    }

    private function entrena(Identificador $equipo): bool
    {
        return $this->conexion->table('entrenador_equipo')
            ->where('usuario_id', $this->usuario()->valor())
            ->where('equipo_id', $equipo->valor())
            ->whereNull('hasta')
            ->whereNull('eliminado_en')
            ->exists();
    }

    /** Las evaluaciones del usuario sobre el jugador, la más reciente primero. */
    private function evaluacionesDelUsuario(Identificador $jugador): Builder
    {
        return $this->conexion->table('evaluaciones_rendimiento')
            ->where('jugador_id', $jugador->valor())
            ->where('entrenador_id', $this->usuario()->valor())
            ->orderByDesc('calculado_en')
            ->orderByDesc('id');
    }

    /**
     * Porcentaje de asistencia con la misma regla de Competencia, `ConteoDeAsistenciaAEntrenamientos` (DD-51): la falta
     * justificada cuenta en el denominador y no como presencia, a dos decimales (HALF_UP).
     */
    private function porcentaje(Builder $registros): PorcentajeDeAsistencia
    {
        $conteo = $registros->selectRaw("count(*) filter (where asistencia.estado = 'presente') as presentes, count(*) as registros, max(asistencia.actualizado_en) as actualizado_en")->first();
        $total = (int) ($conteo->registros ?? 0);

        if ($conteo === null || $total === 0) {
            return new PorcentajeDeAsistencia(null, null);
        }

        return new PorcentajeDeAsistencia(
            NumeroDecimal::desdeTexto((string) $conteo->presentes)->multiplicar(NumeroDecimal::desdeTexto('100'))->dividir(NumeroDecimal::desdeTexto((string) $total), 2),
            self::instante((string) $conteo->actualizado_en),
        );
    }

    private static function jugadorConvocable(stdClass $fila): JugadorConvocable
    {
        return new JugadorConvocable(Identificador::desdeTexto((string) $fila->id), (string) $fila->nombres, (string) $fila->apellidos, (string) $fila->matricula);
    }

    private static function decimal(mixed $valor): ?string
    {
        return $valor === null ? null : NumeroDecimal::desdeTexto((string) $valor)->comoCadena(2);
    }

    private static function instante(string $texto): DateTimeImmutable
    {
        return (new DateTimeImmutable($texto))->setTimezone(new DateTimeZone('UTC'));
    }

    private function usuario(): Identificador
    {
        return $this->usuarioActual->identificador() ?? throw new LogicException('usuario_sin_sesion');
    }
}
