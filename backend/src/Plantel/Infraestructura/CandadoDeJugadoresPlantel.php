<?php

namespace Src\Plantel\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\CandadoDeJugadores;
use Src\Plantel\Dominio\RepositorioDeJugadores;

/** Implementación de Plantel, dueño de `jugadores`: la consulta con candado vive en su repositorio (DA-139). */
final class CandadoDeJugadoresPlantel implements CandadoDeJugadores
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly RepositorioDeJugadores $jugadores,
    ) {}

    public function conJugadoresActivos(array $jugadores, callable $siHayInactivos, callable $escritura): mixed
    {
        return $this->conexion->transaction(function () use ($jugadores, $siHayInactivos, $escritura): mixed {
            $inactivos = $this->jugadores->bloquearActivos($jugadores);
            if ($inactivos !== []) {
                throw $siHayInactivos($inactivos);
            }

            return $escritura();
        });
    }
}
