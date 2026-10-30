<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Convocatoria;
use Src\Competencia\Dominio\Partido;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;

/** Una convocatoria no eliminada de un partido de un equipo que el usuario entrena; si no, 404 (§6.1). */
final class BuscarConvocatoriaDelEntrenador
{
    public function __construct(
        private readonly RepositorioDeConvocatorias $convocatorias,
        private readonly BuscarPartidoDelEntrenador $buscarPartido,
    ) {}

    /**
     * @return array{convocatoria: Convocatoria, partido: Partido}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $id): array
    {
        $convocatoria = $this->convocatorias->buscar($id) ?? throw new RecursoNoEncontrado;

        return ['convocatoria' => $convocatoria, 'partido' => $this->buscarPartido->ejecutar($convocatoria->partidoId())];
    }
}
