<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDePosiciones;

final class RepositorioDePosicionesEloquent implements RepositorioDePosiciones
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function idPorCodigo(string $codigo): ?Identificador
    {
        $id = $this->conexion->table('posiciones')->whereNull('eliminado_en')->where('codigo', $codigo)->value('id');

        return $id === null ? null : Identificador::desdeTexto($id);
    }
}
