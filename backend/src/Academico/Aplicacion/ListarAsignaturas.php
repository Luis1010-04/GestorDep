<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\Asignatura;
use Src\Academico\Dominio\RepositorioDeAsignaturas;

/** Listado paginado de asignaturas vigentes, ordenado por clave. */
final class ListarAsignaturas
{
    public function __construct(private readonly RepositorioDeAsignaturas $asignaturas) {}

    /** @return array{asignaturas: list<Asignatura>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        return [
            'asignaturas' => $this->asignaturas->listar($pagina, $porPagina, $descendente),
            'total' => $this->asignaturas->contar(),
        ];
    }
}
