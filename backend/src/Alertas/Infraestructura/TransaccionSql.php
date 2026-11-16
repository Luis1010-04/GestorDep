<?php

namespace Src\Alertas\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Alertas\Aplicacion\Transaccion;

/** Transacción de la conexión de la aplicación; una transacción interna queda como punto de guardado. */
final class TransaccionSql implements Transaccion
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function ejecutar(callable $operacion): mixed
    {
        return $this->conexion->transaction(fn () => $operacion());
    }
}
