<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\PartidoNoProgramado;
use Src\Competencia\Dominio\RepositorioDeConvocatorias;

/** Quita a un jugador de la convocatoria de un partido programado: borrado lógico con historial que se lleva su alineación (§3.4). */
final class QuitarDeLaConvocatoria
{
    public function __construct(
        private readonly RepositorioDeConvocatorias $convocatorias,
        private readonly BuscarConvocatoriaDelEntrenador $buscar,
    ) {}

    /** @throws RecursoNoEncontrado|PartidoNoProgramado */
    public function ejecutar(Identificador $id): void
    {
        ['convocatoria' => $convocatoria, 'partido' => $partido] = $this->buscar->ejecutar($id);
        $partido->exigirProgramado();

        $this->convocatorias->eliminar($convocatoria);
    }
}
