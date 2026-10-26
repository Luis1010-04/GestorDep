<?php

namespace Src\Plantel\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Plantel\Dominio\RepositorioDeEquipos;

/** Listado paginado de los equipos del entrenador que consulta, ordenado por nombre. */
final class ListarEquipos
{
    public function __construct(
        private readonly RepositorioDeEquipos $equipos,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @return array{equipos: list<EquipoConCategoria>, total: int} */
    public function ejecutar(int $pagina, int $porPagina, bool $descendente): array
    {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        return [
            'equipos' => $this->equipos->listarDelEntrenador($entrenador, $pagina, $porPagina, $descendente),
            'total' => $this->equipos->contarDelEntrenador($entrenador),
        ];
    }
}
