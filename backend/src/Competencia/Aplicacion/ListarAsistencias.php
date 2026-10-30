<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;

/** Registros de asistencia de un entrenamiento, también los de jugadores ya en baja (DA-19). */
final class ListarAsistencias
{
    public function __construct(
        private readonly RepositorioDeAsistenciasDeEntrenamiento $asistencias,
        private readonly BuscarEntrenamientoDelEntrenador $buscarEntrenamiento,
    ) {}

    /**
     * @return array{elementos: list<AsistenciaDeEntrenamiento>, total: int}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $entrenamientoId, int $pagina, int $porPagina): array
    {
        $this->buscarEntrenamiento->ejecutar($entrenamientoId);

        return [
            'elementos' => $this->asistencias->listarDelEntrenamiento($entrenamientoId, $pagina, $porPagina),
            'total' => $this->asistencias->contarDelEntrenamiento($entrenamientoId),
        ];
    }
}
