<?php

namespace Src\Plantel\Dominio;

use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\EquipoConCategoria;

/** Persistencia de equipos y de la asignación de sus entrenadores. Solo ve lo no eliminado. */
interface RepositorioDeEquipos
{
    public function existeEquipo(string $nombre, Identificador $categoriaId): bool;

    /**
     * Guarda el equipo y la fila vigente de `entrenador_equipo` del creador (RN-22), con sus historiales, en una transacción.
     * Si otra petición ganó la carrera, lanza EquipoDuplicado; si la categoría ya no existe, CategoriaInexistente (ambas en Aplicacion).
     */
    public function guardarNuevo(Equipo $equipo, Identificador $entrenador): void;

    /**
     * Solo los equipos con asignación vigente del entrenador. La categoría se muestra aunque esté eliminada.
     *
     * @return list<EquipoConCategoria>
     */
    public function listarDelEntrenador(Identificador $entrenador, int $pagina, int $porPagina, bool $descendente): array;

    public function contarDelEntrenador(Identificador $entrenador): int;
}
