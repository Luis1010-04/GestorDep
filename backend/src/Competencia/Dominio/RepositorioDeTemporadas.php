<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de temporadas. Solo ve las no eliminadas; el borrado es lógico y cada cambio escribe su historial. */
interface RepositorioDeTemporadas
{
    public function existe(Identificador $id): bool;

    public function existeNombre(string $nombre): bool;

    /**
     * Comprueba el cruce con otra temporada y escribe en la misma transacción, con un candado que impide que dos
     * altas simultáneas se crucen (DD-07). Lanza TemporadaSolapada o, si otra petición registró el mismo nombre
     * entre la comprobación y la escritura, TemporadaDuplicada (Aplicacion).
     */
    public function guardarNueva(Temporada $temporada): void;

    /** @return list<Temporada> */
    public function listar(int $pagina, int $porPagina, bool $descendente): array;

    public function contar(): int;

    /** @return bool falso si la temporada no existe o ya estaba eliminada */
    public function eliminar(Identificador $id): bool;
}
