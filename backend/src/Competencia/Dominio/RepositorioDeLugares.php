<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de lugares. Solo ve los no eliminados; el borrado es lógico y cada cambio escribe su historial. */
interface RepositorioDeLugares
{
    public function existe(Identificador $id): bool;

    public function existeNombre(string $nombre): bool;

    /** Si otra petición registró el mismo nombre entre la comprobación y la escritura, lanza LugarDuplicado (Aplicacion). */
    public function guardarNuevo(Lugar $lugar): void;

    /** @return list<Lugar> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si el lugar no existe o ya estaba eliminado */
    public function eliminar(Identificador $id): bool;
}
