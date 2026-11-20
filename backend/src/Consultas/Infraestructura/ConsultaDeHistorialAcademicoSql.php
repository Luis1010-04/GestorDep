<?php

namespace Src\Consultas\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder as ConsultaSql;
use Src\Compartido\Aplicacion\AlcanceDeJugadores;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Consultas\Aplicacion\ConsultaDeHistorialAcademico;
use Src\Consultas\Aplicacion\EntradaDeHistorial;
use stdClass;

/**
 * SQL de solo lectura sobre `calificaciones_historial` y `asistencias_clase_historial` (§4.3). La entidad se une a su
 * inscripción y a la materia del docente sin filtrar `eliminado_en` de la entidad, para que el borrado lógico siga en el
 * historial (HU-26 c2). El autor sale de `usuarios`; es nulo en los cambios hechos sin sesión (DD-35).
 */
final class ConsultaDeHistorialAcademicoSql implements ConsultaDeHistorialAcademico
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly AlcanceDeJugadores $alcance,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function jugadorConMateriaDelDocente(Identificador $jugador): bool
    {
        return $this->inscripcionesDelDocente($jugador)->exists();
    }

    public function calificaciones(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return $this->entradas('calificaciones', 'calificaciones_historial', 'calificaciones', 'calificacion_id', $jugador, $pagina, $porPagina);
    }

    public function asistenciasClase(Identificador $jugador, int $pagina, int $porPagina): array
    {
        return $this->entradas('asistencias-clase', 'asistencias_clase_historial', 'asistencias_clase', 'asistencia_clase_id', $jugador, $pagina, $porPagina);
    }

    /** Inscripciones vigentes del jugador en materias vigentes del docente actual, con el alcance del usuario (bajas incluidas, DD-52). */
    private function inscripcionesDelDocente(Identificador $jugador): ConsultaSql
    {
        $consulta = $this->conexion->table('inscripciones')
            ->join('materias', 'materias.id', '=', 'inscripciones.materia_id')
            ->where('inscripciones.jugador_id', $jugador->valor())
            ->whereNull('inscripciones.eliminado_en')
            ->whereNull('materias.eliminado_en')
            ->where('materias.docente_id', $this->usuarioActual->identificador()?->valor());

        /** @var ConsultaSql */
        return $this->alcance->restringir($this->usuarioActual, $consulta, 'inscripciones.jugador_id', incluirBajas: true);
    }

    /** @return array{entradas: list<EntradaDeHistorial>, total: int} */
    private function entradas(string $recurso, string $historial, string $entidad, string $columna, Identificador $jugador, int $pagina, int $porPagina): array
    {
        $inscripciones = $this->inscripcionesDelDocente($jugador)->select('inscripciones.id');
        $consulta = $this->conexion->table("$historial as h")
            ->join("$entidad as e", 'e.id', '=', "h.$columna")
            ->leftJoin('usuarios as autor', 'autor.id', '=', 'h.usuario_id')
            ->whereIn('e.inscripcion_id', $inscripciones);

        $total = (clone $consulta)->count();
        $filas = $consulta
            ->orderByDesc('h.ocurrido_en')
            ->orderByDesc('h.id')
            ->forPage($pagina, $porPagina)
            ->get(["h.$columna as entidad_id", 'h.version', 'h.operacion', 'h.usuario_id', 'autor.nombre as autor_nombre', 'h.ocurrido_en', 'h.motivo', 'h.datos_anteriores', 'h.datos_nuevos']);

        $entradas = [];
        foreach ($filas as $fila) {
            /** @var stdClass $fila */
            $entradas[] = new EntradaDeHistorial(
                $recurso,
                Identificador::desdeTexto($fila->entidad_id),
                (int) $fila->version,
                $fila->operacion,
                $fila->usuario_id === null ? null : Identificador::desdeTexto($fila->usuario_id),
                $fila->autor_nombre,
                (new DateTimeImmutable($fila->ocurrido_en))->setTimezone(new DateTimeZone('UTC')),
                $fila->motivo,
                $this->datos($fila->datos_anteriores),
                $this->datos($fila->datos_nuevos),
            );
        }

        return ['entradas' => $entradas, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    private function datos(?string $json): ?array
    {
        if ($json === null) {
            return null;
        }

        /** @var array<string, mixed> */
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}
