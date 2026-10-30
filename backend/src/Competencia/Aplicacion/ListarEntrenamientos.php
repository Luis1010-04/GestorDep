<?php

namespace Src\Competencia\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Entrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;

/** Listado paginado de los entrenamientos de un equipo que el usuario entrena, con `inicia_en` en [`$desde`, `$hasta`). */
final class ListarEntrenamientos
{
    public function __construct(
        private readonly RepositorioDeEntrenamientos $entrenamientos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
    ) {}

    /**
     * @return array{elementos: list<Entrenamiento>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $equipoId, ?DateTimeImmutable $desde, ?DateTimeImmutable $hasta, int $pagina, int $porPagina, bool $descendente): array
    {
        $this->comprobarEquipo->ejecutar($equipoId);

        return [
            'elementos' => $this->entrenamientos->listarDelEquipo($equipoId, $desde, $hasta, $pagina, $porPagina, $descendente),
            'total' => $this->entrenamientos->contarDelEquipo($equipoId, $desde, $hasta),
        ];
    }
}
