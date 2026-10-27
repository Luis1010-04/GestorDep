<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\RepositorioDePeriodos;

/** Listado paginado de periodos vigentes, ordenado por `fecha_inicio`, con la marca del periodo vigente (DD-45). */
final class ListarPeriodos
{
    public function __construct(
        private readonly RepositorioDePeriodos $periodos,
        private readonly PeriodoVigente $periodoVigente,
    ) {}

    /** @return array{periodos: list<PeriodoConVigencia>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        $vigente = $this->periodoVigente->identificador();

        return [
            'periodos' => array_map(
                fn ($periodo) => new PeriodoConVigencia($periodo, $vigente?->igualA($periodo->id()) === true),
                $this->periodos->listar($pagina, $porPagina, $descendente),
            ),
            'total' => $this->periodos->contar(),
        ];
    }
}
