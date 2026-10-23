<?php

namespace Src\Plantel\Aplicacion;

use Src\Plantel\Dominio\Categoria;
use Src\Plantel\Dominio\RepositorioDeCategorias;

/** Listado paginado de categorías vigentes, ordenado por nombre. */
final class ListarCategorias
{
    public function __construct(private readonly RepositorioDeCategorias $categorias) {}

    /** @return array{categorias: list<Categoria>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'categorias' => $this->categorias->listar($pagina, $porPagina, $descendente),
            'total' => $this->categorias->contar(),
        ];
    }
}
