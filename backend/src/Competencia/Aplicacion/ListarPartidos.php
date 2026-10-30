<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\EstadoDePartido;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDePartidos;

/** Partidos de un equipo que el usuario entrena; un filtro de temporada inexistente da una lista vacía (DA-26). */
final class ListarPartidos
{
    public function __construct(
        private readonly RepositorioDePartidos $partidos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
    ) {}

    /**
     * @return array{elementos: list<Partido>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $equipoId, ?Identificador $temporadaId, ?EstadoDePartido $estado, int $pagina, int $porPagina, bool $descendente): array
    {
        $this->comprobarEquipo->ejecutar($equipoId);

        return [
            'elementos' => $this->partidos->listarDelEquipo($equipoId, $temporadaId, $estado, $pagina, $porPagina, $descendente),
            'total' => $this->partidos->contarDelEquipo($equipoId, $temporadaId, $estado),
        ];
    }
}
