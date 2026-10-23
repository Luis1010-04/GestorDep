<?php

namespace Src\Plantel\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de categorías. Solo ve las no eliminadas; el borrado es lógico. */
interface RepositorioDeCategorias
{
    public function existeNombre(string $nombre): bool;

    /** Si otra petición registró el mismo nombre entre la comprobación y la escritura, lanza CategoriaDuplicada (Aplicacion). */
    public function guardarNueva(Categoria $categoria): void;

    /** @return list<Categoria> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si la categoría no existe o ya estaba eliminada */
    public function eliminar(Identificador $id): bool;
}
