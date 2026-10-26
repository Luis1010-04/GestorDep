<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Plantel\Aplicacion\BuscadorDeJugadorPorMatricula;
use Src\Plantel\Aplicacion\JugadorBuscado;

/** Excepción de visibilidad de RN-20: no usa `AlcanceDeJugadores` y solo lee nombres, apellidos y matrícula. */
final class BusquedaDeJugadorPorMatricula implements BuscadorDeJugadorPorMatricula
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    /** Compara con `lower()` para usar el índice `uq_jugadores_matricula`. */
    public function buscar(string $matricula): ?JugadorBuscado
    {
        $fila = $this->conexion->table('jugadores')
            ->whereNull('eliminado_en')
            ->whereRaw('lower(matricula) = lower(?)', [$matricula])
            ->first(['nombres', 'apellidos', 'matricula']);

        return $fila === null ? null : new JugadorBuscado($fila->nombres, $fila->apellidos, $fila->matricula);
    }
}
