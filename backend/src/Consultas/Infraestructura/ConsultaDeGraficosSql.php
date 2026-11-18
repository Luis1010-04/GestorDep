<?php

namespace Src\Consultas\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Consultas\Aplicacion\ConsultaDeGraficos;
use Src\Consultas\Aplicacion\DatosDeSerie;
use Src\Consultas\Aplicacion\MateriaDelGrafico;
use Src\Consultas\Aplicacion\PuntoDeSerie;
use stdClass;

/**
 * SQL de solo lectura sobre `rendimientos_partido`, `partidos`, `mediciones_corporales`, `resumenes_academicos`, `inscripciones`,
 * `materias` y `calificaciones` (§4.3). Lo que registra un entrenador sale solo de lo que registró el usuario (RN-14); lo del
 * docente, solo de sus materias (RN-13). Las fechas de partido son del día en la zona de presentación.
 */
final class ConsultaDeGraficosSql implements ConsultaDeGraficos
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function rendimientoDelJugador(Identificador $jugador, ?Identificador $equipo = null): DatosDeSerie
    {
        $registros = $this->rendimientosDelUsuario()->where('rendimiento.jugador_id', $jugador->valor());
        if ($equipo !== null) {
            $registros->where('rendimiento.equipo_id', $equipo->valor());
        }

        $filas = $registros
            ->orderBy('partido.inicia_en')
            ->orderBy('rendimiento.id')
            ->selectRaw("to_char(partido.inicia_en at time zone ?, 'YYYY-MM-DD') as fecha, rendimiento.calificacion_general as valor, rendimiento.actualizado_en", [$this->zona()])
            ->get();

        return $this->serie($filas->all(), fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enFecha((string) $fila->fecha, (int) $fila->valor));
    }

    public function rendimientoDelEquipo(Identificador $equipo): DatosDeSerie
    {
        $filas = $this->rendimientosDelUsuario()
            ->where('partido.equipo_id', $equipo->valor())
            ->groupBy('partido.id', 'partido.inicia_en')
            ->orderBy('partido.inicia_en')
            ->orderBy('partido.id')
            ->selectRaw("to_char(partido.inicia_en at time zone ?, 'YYYY-MM-DD') as fecha, round(avg(rendimiento.calificacion_general), 2) as valor, max(rendimiento.actualizado_en) as actualizado_en", [$this->zona()])
            ->get();

        return $this->serie($filas->all(), fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enFecha((string) $fila->fecha, NumeroDecimal::desdeTexto((string) $fila->valor)->comoCadena(2)));
    }

    public function medicionesDelJugador(Identificador $jugador): array
    {
        $filas = $this->conexion->table('mediciones_corporales')
            ->where('jugador_id', $jugador->valor())
            ->where('registrado_por', $this->usuario()->valor())
            ->whereNull('eliminado_en')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get(['periodo', 'peso_kg', 'estatura_cm', 'imc', 'actualizado_en'])
            ->all();

        return [
            'peso_kg' => $this->serie($filas, fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enPeriodo((string) $fila->periodo, NumeroDecimal::desdeTexto((string) $fila->peso_kg)->comoCadena(2))),
            'estatura_cm' => $this->serie($filas, fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enPeriodo((string) $fila->periodo, NumeroDecimal::desdeTexto((string) $fila->estatura_cm)->comoCadena(1))),
            'imc' => $this->serie($filas, fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enPeriodo((string) $fila->periodo, NumeroDecimal::desdeTexto((string) $fila->imc)->comoCadena(2))),
        ];
    }

    public function resumenAcademicoDelJugador(Identificador $jugador): DatosDeSerie
    {
        $filas = $this->conexion->table('resumenes_academicos as resumen')
            ->join('periodos_escolares as periodo', 'periodo.id', '=', 'resumen.periodo_id')
            ->where('resumen.jugador_id', $jugador->valor())
            ->whereNotNull('resumen.promedio')
            ->orderBy('periodo.fecha_inicio')
            ->orderBy('periodo.id')
            ->get(['periodo.id as periodo_id', 'periodo.nombre as periodo_nombre', 'resumen.promedio', 'resumen.calculado_en as actualizado_en'])
            ->all();

        return $this->serie($filas, fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enPeriodo(
            ['id' => (string) $fila->periodo_id, 'nombre' => (string) $fila->periodo_nombre],
            NumeroDecimal::desdeTexto((string) $fila->promedio)->comoCadena(2),
        ));
    }

    public function materiasDelDocente(Identificador $jugador): array
    {
        return array_values(array_map(fn (stdClass $fila): MateriaDelGrafico => new MateriaDelGrafico(Identificador::desdeTexto($fila->id), $fila->clave, $fila->nombre), $this->conexion->table('inscripciones as inscripcion')
            ->join('materias as materia', 'materia.id', '=', 'inscripcion.materia_id')
            ->join('asignaturas as asignatura', 'asignatura.id', '=', 'materia.asignatura_id')
            ->join('periodos_escolares as periodo', 'periodo.id', '=', 'materia.periodo_id')
            ->where('inscripcion.jugador_id', $jugador->valor())
            ->where('materia.docente_id', $this->usuario()->valor())
            ->whereNull('inscripcion.eliminado_en')
            ->whereNull('materia.eliminado_en')
            ->orderBy('periodo.fecha_inicio')
            ->orderBy('asignatura.clave')
            ->orderBy('materia.id')
            ->get(['materia.id', 'asignatura.clave', 'asignatura.nombre'])
            ->all()));
    }

    public function calificacionesDeLaMateria(Identificador $jugador, Identificador $materia): DatosDeSerie
    {
        $filas = $this->conexion->table('calificaciones as calificacion')
            ->join('inscripciones as inscripcion', 'inscripcion.id', '=', 'calificacion.inscripcion_id')
            ->join('materias as materia', 'materia.id', '=', 'inscripcion.materia_id')
            ->where('inscripcion.jugador_id', $jugador->valor())
            ->where('inscripcion.materia_id', $materia->valor())
            ->where('materia.docente_id', $this->usuario()->valor())
            ->whereNull('calificacion.eliminado_en')
            ->whereNull('inscripcion.eliminado_en')
            ->whereNull('materia.eliminado_en')
            ->orderBy('calificacion.fecha')
            ->orderBy('calificacion.id')
            ->get(['calificacion.fecha', 'calificacion.valor', 'calificacion.actualizado_en'])
            ->all();

        return $this->serie($filas, fn (stdClass $fila): PuntoDeSerie => PuntoDeSerie::enFecha((string) $fila->fecha, NumeroDecimal::desdeTexto((string) $fila->valor)->comoCadena(2)));
    }

    public function jugadorEnEquipos(Identificador $jugador, array $equipos): bool
    {
        return $equipos !== [] && $this->conexion->table('jugador_equipo')
            ->where('jugador_id', $jugador->valor())
            ->whereIn('equipo_id', array_map(fn (Identificador $equipo): string => $equipo->valor(), $equipos))
            ->whereNull('hasta')
            ->whereNull('eliminado_en')
            ->exists();
    }

    /** Los rendimientos de partido no borrados que registró el usuario, con su partido. */
    private function rendimientosDelUsuario(): Builder
    {
        return $this->conexion->table('rendimientos_partido as rendimiento')
            ->join('partidos as partido', 'partido.id', '=', 'rendimiento.partido_id')
            ->where('rendimiento.registrado_por', $this->usuario()->valor())
            ->whereNull('rendimiento.eliminado_en')
            ->whereNull('partido.eliminado_en');
    }

    /**
     * @param  array<int, stdClass>  $filas  cada una con `actualizado_en`
     * @param  callable(stdClass): PuntoDeSerie  $punto
     */
    private function serie(array $filas, callable $punto): DatosDeSerie
    {
        $actualizadoEn = null;
        foreach ($filas as $fila) {
            $momento = (new DateTimeImmutable((string) $fila->actualizado_en))->setTimezone(new DateTimeZone('UTC'));
            $actualizadoEn = $actualizadoEn === null || $momento > $actualizadoEn ? $momento : $actualizadoEn;
        }

        return new DatosDeSerie(array_values(array_map($punto, $filas)), $actualizadoEn);
    }

    private function usuario(): Identificador
    {
        return $this->usuarioActual->identificador() ?? throw new LogicException('usuario_sin_sesion');
    }

    private function zona(): string
    {
        return (string) config('app.zona_presentacion');
    }
}
