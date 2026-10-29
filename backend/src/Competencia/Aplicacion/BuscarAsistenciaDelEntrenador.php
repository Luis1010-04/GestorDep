<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;

/** Un registro de asistencia de un entrenamiento de un equipo que el usuario entrena; si no, 404 (§6.1). */
final class BuscarAsistenciaDelEntrenador
{
    public function __construct(
        private readonly RepositorioDeAsistenciasDeEntrenamiento $asistencias,
        private readonly BuscarEntrenamientoDelEntrenador $buscarEntrenamiento,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id): AsistenciaDeEntrenamiento
    {
        $asistencia = $this->asistencias->buscar($id) ?? throw new RecursoNoEncontrado;
        $this->buscarEntrenamiento->ejecutar($asistencia->entrenamientoId());

        return $asistencia;
    }
}
