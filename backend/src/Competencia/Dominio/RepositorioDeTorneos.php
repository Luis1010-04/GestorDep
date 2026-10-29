<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de torneos. Solo ve los no eliminados; el borrado es lógico y cada cambio escribe su historial. */
interface RepositorioDeTorneos
{
    public function existeNombre(string $nombre, Identificador $temporadaId): bool;

    /** Si otra petición registró el mismo nombre en la temporada entre la comprobación y la escritura, lanza TorneoDuplicado (Aplicacion). */
    public function guardarNuevo(Torneo $torneo): void;

    /** @return list<Torneo> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si el torneo no existe o ya estaba eliminado */
    public function eliminar(Identificador $id): bool;
}
