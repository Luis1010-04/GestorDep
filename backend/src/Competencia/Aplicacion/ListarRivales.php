<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\RepositorioDeRivales;
use Src\Competencia\Dominio\Rival;

/** Listado paginado de rivales no eliminados, ordenado por `nombre` (HU-28). */
final class ListarRivales
{
    public function __construct(private readonly RepositorioDeRivales $rivales) {}

    /** @return array{elementos: list<Rival>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'elementos' => $this->rivales->listar($pagina, $porPagina, $descendente),
            'total' => $this->rivales->contar(),
        ];
    }
}
