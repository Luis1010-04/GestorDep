<?php

namespace Src\Competencia\Aplicacion;

use Src\Competencia\Dominio\RepositorioDeTorneos;
use Src\Competencia\Dominio\Torneo;

/** Listado paginado de torneos no eliminados, ordenado por `nombre` (HU-28). */
final class ListarTorneos
{
    public function __construct(private readonly RepositorioDeTorneos $torneos) {}

    /** @return array{elementos: list<Torneo>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'elementos' => $this->torneos->listar($pagina, $porPagina, $descendente),
            'total' => $this->torneos->contar(),
        ];
    }
}
