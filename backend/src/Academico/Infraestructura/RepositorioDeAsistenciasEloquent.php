<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;
use Src\Academico\Aplicacion\AsistenciaDuplicada;
use Src\Academico\Dominio\AsistenciaDeClase;
use Src\Academico\Dominio\EstadoDeAsistencia;
use Src\Academico\Dominio\RepositorioDeAsistencias;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/** Escribe `asistencias_clase` y `asistencias_clase_historial` en la misma transacción, con la instantánea completa (RNF-02, DD-39). */
final class RepositorioDeAsistenciasEloquent implements RepositorioDeAsistencias
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(AsistenciaDeClase $asistencia): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $asistencia->actualizadoEn()->format('Y-m-d H:i:sP');
        $datos = [
            'inscripcion_id' => $asistencia->inscripcionId()->valor(),
            'fecha' => $asistencia->fecha()->format('Y-m-d'),
            'estado' => $asistencia->estado()->value,
        ];

        try {
            $this->historial->registrar(
                'asistencias_clase',
                'asistencia_clase_id',
                $asistencia->id(),
                'crear',
                null,
                $datos,
                fn () => $this->conexion->table('asistencias_clase')->insert([
                    'id' => $asistencia->id()->valor(),
                    ...$datos,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $asistencia->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new AsistenciaDuplicada;
        }
    }

    public function corregirEstado(Identificador $asistenciaId, EstadoDeAsistencia $estado, DateTimeImmutable $instante): bool
    {
        $autor = $this->usuarioActual->identificador()?->valor();

        return $this->conexion->transaction(function () use ($asistenciaId, $estado, $instante, $autor): bool {
            $fila = $this->conexion->table('asistencias_clase')
                ->where('id', $asistenciaId->valor())
                ->whereNull('eliminado_en')
                ->lockForUpdate()
                ->first(['inscripcion_id', 'fecha', 'estado']);
            if ($fila === null) {
                throw new LogicException('asistencia_inexistente');
            }
            if ($fila->estado === $estado->value) {
                return false;
            }

            $anteriores = ['inscripcion_id' => $fila->inscripcion_id, 'fecha' => $fila->fecha, 'estado' => $fila->estado];
            $nuevos = [...$anteriores, 'estado' => $estado->value];
            $this->historial->registrar(
                'asistencias_clase',
                'asistencia_clase_id',
                $asistenciaId,
                'actualizar',
                $anteriores,
                $nuevos,
                fn () => $this->conexion->table('asistencias_clase')->where('id', $asistenciaId->valor())->update([
                    'estado' => $estado->value,
                    'actualizado_en' => $instante->format('Y-m-d H:i:sP'),
                    'actualizado_por' => $autor,
                ]),
                instante: $instante,
            );

            return true;
        });
    }
}
