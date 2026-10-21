<?php

namespace Src\Compartido\Infraestructura\Historial;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Reloj;

/**
 * Escribe un cambio y su registro en {tabla}_historial en la misma transaccion
 * (docs/arquitectura.md, seccion 9): si cualquiera de los dos falla, no queda ninguno.
 */
final class EscritorDeHistorial
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly Reloj $reloj,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * @param  array<string, mixed>|null  $datosAnteriores  nulo al crear
     * @param  array<string, mixed>|null  $datosNuevos  nulo al eliminar
     * @param  callable(): mixed  $cambio  escribe el cambio en la tabla de la entidad
     */
    public function registrar(
        string $tabla,
        string $columnaEntidad,
        Identificador $entidadId,
        string $operacion,
        ?array $datosAnteriores,
        ?array $datosNuevos,
        callable $cambio,
        ?string $motivo = null,
    ): void {
        $this->conexion->transaction(function () use ($tabla, $columnaEntidad, $entidadId, $operacion, $datosAnteriores, $datosNuevos, $cambio, $motivo) {
            $cambio();

            $historial = "{$tabla}_historial";
            // Dos cambios simultaneos con la misma version los detiene la restriccion unica (entidad, version).
            $version = (int) $this->conexion->table($historial)
                ->where($columnaEntidad, $entidadId->valor())
                ->max('version') + 1;

            $this->conexion->table($historial)->insert([
                'id' => Identificador::generar()->valor(),
                $columnaEntidad => $entidadId->valor(),
                'version' => $version,
                'operacion' => $operacion,
                'datos_anteriores' => $datosAnteriores === null ? null : json_encode($datosAnteriores, JSON_THROW_ON_ERROR),
                'datos_nuevos' => $datosNuevos === null ? null : json_encode($datosNuevos, JSON_THROW_ON_ERROR),
                'usuario_id' => $this->usuarioActual->identificador()->valor(),
                'ocurrido_en' => $this->reloj->ahora()->format('Y-m-d H:i:s.uP'),
                'motivo' => $motivo,
            ]);
        });
    }
}
