<?php

namespace Src\Competencia\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Competencia\Aplicacion\AsistenciaYaRegistrada;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\EstadoDeAsistencia;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;
use stdClass;

/** Escribe `asistencias_entrenamiento` y su historial en la misma transacción (RNF-02). */
final class RepositorioDeAsistenciasDeEntrenamientoEloquent implements RepositorioDeAsistenciasDeEntrenamiento
{
    private const COLUMNAS = ['id', 'entrenamiento_id', 'jugador_id', 'estado', 'actualizado_en'];

    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function hayAlgunaDe(Identificador $entrenamientoId, array $jugadores): bool
    {
        return $this->conexion->table('asistencias_entrenamiento')
            ->whereNull('eliminado_en')
            ->where('entrenamiento_id', $entrenamientoId->valor())
            ->whereIn('jugador_id', array_map(fn (Identificador $jugador) => $jugador->valor(), $jugadores))
            ->exists();
    }

    public function guardarNuevas(array $asistencias): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();

        try {
            $this->conexion->transaction(function () use ($asistencias, $autor): void {
                foreach ($asistencias as $asistencia) {
                    $instante = $asistencia->actualizadoEn()->format('Y-m-d H:i:sP');
                    $this->historial->registrar(
                        'asistencias_entrenamiento',
                        'asistencia_entrenamiento_id',
                        $asistencia->id(),
                        'crear',
                        null,
                        $this->instantanea($asistencia),
                        fn () => $this->conexion->table('asistencias_entrenamiento')->insert([
                            'id' => $asistencia->id()->valor(),
                            ...$this->instantanea($asistencia),
                            'creado_en' => $instante,
                            'actualizado_en' => $instante,
                            'creado_por' => $autor,
                            'actualizado_por' => $autor,
                        ]),
                        instante: $asistencia->actualizadoEn(),
                    );
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw new AsistenciaYaRegistrada;
        }
    }

    public function buscar(Identificador $id): ?AsistenciaDeEntrenamiento
    {
        $fila = $this->conexion->table('asistencias_entrenamiento')->whereNull('eliminado_en')->where('id', $id->valor())->first(self::COLUMNAS);

        return $fila === null ? null : $this->aAsistencia($fila);
    }

    public function actualizar(AsistenciaDeEntrenamiento $anterior, AsistenciaDeEntrenamiento $nueva): void
    {
        $this->historial->registrar(
            'asistencias_entrenamiento',
            'asistencia_entrenamiento_id',
            $nueva->id(),
            'actualizar',
            $this->instantanea($anterior),
            $this->instantanea($nueva),
            fn () => $this->conexion->table('asistencias_entrenamiento')->where('id', $nueva->id()->valor())->update([
                'estado' => $nueva->estado()->value,
                'actualizado_en' => $nueva->actualizadoEn()->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $nueva->actualizadoEn(),
        );
    }

    public function listarDelEntrenamiento(Identificador $entrenamientoId, int $pagina, int $porPagina): array
    {
        $asistencias = $this->conexion->table('asistencias_entrenamiento')
            ->whereNull('eliminado_en')
            ->where('entrenamiento_id', $entrenamientoId->valor())
            ->orderBy('creado_en')
            ->orderBy('id')
            ->offset(($pagina - 1) * $porPagina)
            ->limit($porPagina)
            ->get(self::COLUMNAS)
            ->map(fn (stdClass $fila) => $this->aAsistencia($fila))
            ->all();

        return array_values($asistencias);
    }

    public function contarDelEntrenamiento(Identificador $entrenamientoId): int
    {
        return $this->conexion->table('asistencias_entrenamiento')
            ->whereNull('eliminado_en')
            ->where('entrenamiento_id', $entrenamientoId->valor())
            ->count();
    }

    /** @return array{entrenamiento_id: string, jugador_id: string, estado: string} */
    private function instantanea(AsistenciaDeEntrenamiento $asistencia): array
    {
        return [
            'entrenamiento_id' => $asistencia->entrenamientoId()->valor(),
            'jugador_id' => $asistencia->jugadorId()->valor(),
            'estado' => $asistencia->estado()->value,
        ];
    }

    private function aAsistencia(stdClass $fila): AsistenciaDeEntrenamiento
    {
        return new AsistenciaDeEntrenamiento(
            Identificador::desdeTexto($fila->id),
            Identificador::desdeTexto($fila->entrenamiento_id),
            Identificador::desdeTexto($fila->jugador_id),
            EstadoDeAsistencia::from($fila->estado),
            (new DateTimeImmutable($fila->actualizado_en))->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
