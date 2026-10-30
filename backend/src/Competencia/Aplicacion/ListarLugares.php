<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\Lugar;
use Src\Competencia\Dominio\RepositorioDeLugares;

/** Listado paginado de lugares no eliminados, ordenado por `nombre` (HU-28). */
final class ListarLugares
{
    public function __construct(private readonly RepositorioDeLugares $lugares) {}

    /** @return array{elementos: list<Lugar>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'elementos' => $this->lugares->listar($pagina, $porPagina, $descendente),
            'total' => $this->lugares->contar(),
        ];
    }
}
