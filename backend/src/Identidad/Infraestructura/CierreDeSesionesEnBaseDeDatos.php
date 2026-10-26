<?php

namespace Src\Identidad\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Aplicacion\CierreDeSesiones;

/** Borra las filas de `sessions` (driver `database`, DD-28) del usuario, salvo la actual. */
final class CierreDeSesionesEnBaseDeDatos implements CierreDeSesiones
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function cerrarOtras(Identificador $usuario, string $sesionActual): void
    {
        $this->conexion->table('sessions')
            ->where('user_id', $usuario->valor())
            ->where('id', '!=', $sesionActual)
            ->delete();
    }
}
