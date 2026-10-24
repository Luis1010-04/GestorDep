<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Listado paginado de los jugadores del entrenador que consulta (HU-02). */
final class ListarJugadores
{
    public function __construct(private readonly ConsultaDeJugadores $jugadores) {}

    /**
     * @param  'apellidos'|'nombres'  $campoDeOrden
     * @return array{encontrados: list<JugadorConEquipos>, total: int}
     */
    public function ejecutar(?Identificador $equipoId, string $campoDeOrden, bool $descendente, int $pagina, int $porPagina): array
    {
        return [
            'encontrados' => $this->jugadores->listar($equipoId, $campoDeOrden, $descendente, $pagina, $porPagina),
            'total' => $this->jugadores->contar($equipoId),
        ];
    }
}
