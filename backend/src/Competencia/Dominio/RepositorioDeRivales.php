<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de rivales. Solo ve los no eliminados; el borrado es lógico y cada cambio escribe su historial. */
interface RepositorioDeRivales
{
    public function existe(Identificador $id): bool;

    public function existeNombre(string $nombre): bool;

    /** Si otra petición registró el mismo nombre entre la comprobación y la escritura, lanza RivalDuplicado (Aplicacion). */
    public function guardarNuevo(Rival $rival): void;

    /** @return list<Rival> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si el rival no existe o ya estaba eliminado */
    public function eliminar(Identificador $id): bool;
}
