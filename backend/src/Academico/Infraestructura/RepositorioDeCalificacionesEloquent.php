<?php

namespace Src\Academico\Infraestructura;

use DateTimeImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;
use Src\Academico\Aplicacion\CalificacionDuplicada;
use Src\Academico\Dominio\Calificacion;
use Src\Academico\Dominio\RepositorioDeCalificaciones;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;

/** Escribe `calificaciones` y `calificaciones_historial` en la misma transacción, con la instantánea completa (RNF-02, DD-39). */
final class RepositorioDeCalificacionesEloquent implements RepositorioDeCalificaciones
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function guardarNueva(Calificacion $calificacion): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $instante = $calificacion->actualizadoEn()->format('Y-m-d H:i:sP');
        $datos = [
            'inscripcion_id' => $calificacion->inscripcionId()->valor(),
            'tipo' => $calificacion->tipo()->value,
            'numero_parcial' => $calificacion->numeroParcial(),
            'valor' => $calificacion->valor()->comoCadena(2),
            'criterio_id' => $calificacion->criterioId()->valor(),
            'fecha' => $calificacion->fecha()->format('Y-m-d'),
        ];

        try {
            $this->historial->registrar(
                'calificaciones',
                'calificacion_id',
                $calificacion->id(),
                'crear',
                null,
                $datos,
                fn () => $this->conexion->table('calificaciones')->insert([
                    'id' => $calificacion->id()->valor(),
                    ...$datos,
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]),
                instante: $calificacion->actualizadoEn(),
            );
        } catch (UniqueConstraintViolationException) {
            throw new CalificacionDuplicada;
        }
    }

    public function corregirValor(Identificador $calificacionId, NumeroDecimal $valor, DateTimeImmutable $instante): bool
    {
        $autor = $this->usuarioActual->identificador()?->valor();

        return $this->conexion->transaction(function () use ($calificacionId, $valor, $instante, $autor): bool {
            $fila = $this->conexion->table('calificaciones')
                ->where('id', $calificacionId->valor())
                ->whereNull('eliminado_en')
                ->lockForUpdate()
                ->first(['inscripcion_id', 'tipo', 'numero_parcial', 'valor', 'criterio_id', 'fecha']);
            if ($fila === null) {
                throw new LogicException('calificacion_inexistente');
            }
            if (NumeroDecimal::desdeTexto((string) $fila->valor)->igualA($valor)) {
                return false;
            }

            $anteriores = [
                'inscripcion_id' => $fila->inscripcion_id,
                'tipo' => $fila->tipo,
                'numero_parcial' => $fila->numero_parcial,
                'valor' => (string) $fila->valor,
                'criterio_id' => $fila->criterio_id,
                'fecha' => $fila->fecha,
            ];
            $nuevos = [...$anteriores, 'valor' => $valor->comoCadena(2)];
            $this->historial->registrar(
                'calificaciones',
                'calificacion_id',
                $calificacionId,
                'actualizar',
                $anteriores,
                $nuevos,
                fn () => $this->conexion->table('calificaciones')->where('id', $calificacionId->valor())->update([
                    'valor' => $nuevos['valor'],
                    'actualizado_en' => $instante->format('Y-m-d H:i:sP'),
                    'actualizado_por' => $autor,
                ]),
                instante: $instante,
            );

            return true;
        });
    }
}
