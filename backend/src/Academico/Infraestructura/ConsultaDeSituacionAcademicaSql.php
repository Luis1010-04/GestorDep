<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use LogicException;
use Src\Academico\Aplicacion\ConsultaDeSituacionAcademica;
use Src\Academico\Aplicacion\MateriaDelDocente;
use Src\Academico\Aplicacion\ResumenLeido;
use Src\Academico\Dominio\NivelDeRiesgo;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use stdClass;

/**
 * Lecturas de la situación académica. El único acceso a `jugadores` pasa por `AlcanceDeJugadores::restringir()`, al final
 * de las condiciones y con las bajas incluidas (DD-30, DD-52); lo demás lee tablas de Académico.
 */
final class ConsultaDeSituacionAcademicaSql implements ConsultaDeSituacionAcademica
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function jugadorVisible(Identificador $jugadorId): bool
    {
        $consulta = $this->conexion->table('jugadores')
            ->where('jugadores.id', $jugadorId->valor())
            ->whereNull('jugadores.eliminado_en');

        return $this->alcance->restringir($this->usuarioActual, $consulta, 'jugadores.id', incluirBajas: true)->exists();
    }

    public function nombreDelPeriodo(Identificador $periodoId): string
    {
        $nombre = $this->conexion->table('periodos_escolares')->where('id', $periodoId->valor())->value('nombre');
        if ($nombre === null) {
            throw new LogicException('periodo_inexistente');
        }

        return (string) $nombre;
    }

    public function resumen(Identificador $jugadorId, Identificador $periodoId): ?ResumenLeido
    {
        $fila = $this->conexion->table('resumenes_academicos')
            ->where('jugador_id', $jugadorId->valor())
            ->where('periodo_id', $periodoId->valor())
            ->first(['promedio', 'materias_reprobadas', 'materias_sin_calificar', 'porcentaje_asistencia', 'nivel_riesgo', 'calculado_en']);

        return $fila === null ? null : new ResumenLeido(
            $fila->promedio === null ? null : NumeroDecimal::desdeTexto((string) $fila->promedio),
            (int) $fila->materias_reprobadas,
            (int) $fila->materias_sin_calificar,
            $fila->porcentaje_asistencia === null ? null : NumeroDecimal::desdeTexto((string) $fila->porcentaje_asistencia),
            $fila->nivel_riesgo === null ? null : NivelDeRiesgo::from($fila->nivel_riesgo),
            (new DateTimeImmutable($fila->calculado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }

    public function esDocenteDelJugador(Identificador $jugadorId, Identificador $docenteId): bool
    {
        return $this->conexion->table('inscripciones')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
            ->where('inscripciones.jugador_id', $jugadorId->valor())
            ->whereNull('inscripciones.eliminado_en')
            ->whereNull('materias.eliminado_en')
            ->where('materias.docente_id', $docenteId->valor())
            ->exists();
    }

    public function materiasDelDocente(Identificador $jugadorId, Identificador $docenteId, Identificador $periodoId): array
    {
        $filas = $this->conexion->table('inscripciones')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
            ->join('asignaturas', 'asignaturas.id', '=', 'materias.asignatura_id')
            ->where('inscripciones.jugador_id', $jugadorId->valor())
            ->whereNull('inscripciones.eliminado_en')
            ->whereNull('materias.eliminado_en')
            ->where('materias.docente_id', $docenteId->valor())
            ->where('materias.periodo_id', $periodoId->valor())
            ->orderBy('asignaturas.clave')
            ->orderBy('materias.id')
            ->get(['materias.id as materia_id', 'inscripciones.id as inscripcion_id', 'asignaturas.clave', 'asignaturas.nombre']);

        return array_values($filas->map(fn (stdClass $fila): MateriaDelDocente => new MateriaDelDocente(
            Identificador::desdeTexto($fila->materia_id),
            Identificador::desdeTexto($fila->inscripcion_id),
            (string) $fila->clave,
            (string) $fila->nombre,
        ))->all());
    }
}
