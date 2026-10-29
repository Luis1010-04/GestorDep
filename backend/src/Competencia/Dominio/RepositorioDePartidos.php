<?php

namespace Src\Competencia\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de partidos. Nunca se borran: se cancelan. Cada alta y cada cambio escriben su historial. */
interface RepositorioDePartidos
{
    public function guardarNuevo(Partido $partido): void;

    /** Escribe `$nuevo` y el historial con la instantánea completa de antes y de después. */
    public function actualizar(Partido $anterior, Partido $nuevo): void;

    public function buscar(Identificador $id): ?Partido;

    /** @return list<Partido> ordenados por `inicia_en` */
    public function listarDelEquipo(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado, int $pagina, int $porPagina, bool $descendente): array;

    public function contarDelEquipo(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado): int;
}
