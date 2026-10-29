<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\RepositorioDeTemporadas;
use Src\Competencia\Dominio\Temporada;

/** Listado paginado de temporadas no eliminadas, ordenado por `fecha_inicio` (HU-28). */
final class ListarTemporadas
{
    public function __construct(private readonly RepositorioDeTemporadas $temporadas) {}

    /** @return array{elementos: list<Temporada>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'elementos' => $this->temporadas->listar($pagina, $porPagina, $descendente),
            'total' => $this->temporadas->contar(),
        ];
    }
}
