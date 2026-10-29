<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Convocatoria;

/** Convocatoria de un partido de un equipo que el usuario entrena, sin jugadores en baja (HU-29 criterio 3). */
final class ListarConvocatoria
{
    public function __construct(
        private readonly ConsultaDeConvocatoria $consulta,
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
    ) {}

    /**
     * @return array{elementos: list<Convocatoria>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $partidoId, int $pagina, int $porPagina): array
    {
        $this->buscarPartido->ejecutar($partidoId);

        return [
            'elementos' => $this->consulta->delPartido($partidoId, $pagina, $porPagina),
            'total' => $this->consulta->contarDelPartido($partidoId),
        ];
    }
}
