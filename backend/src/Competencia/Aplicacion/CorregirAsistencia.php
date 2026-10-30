<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;
use Src\Competencia\Dominio\EstadoDeAsistencia;
use Src\Competencia\Dominio\RepositorioDeAsistenciasDeEntrenamiento;

/** Corrección del estado de un registro (HU-11), con historial; no exige que el jugador siga en el equipo (DA-18). */
final class CorregirAsistencia
{
    public function __construct(
        private readonly RepositorioDeAsistenciasDeEntrenamiento $asistencias,
        private readonly BuscarAsistenciaDelEntrenador $buscar,
        private readonly Reloj $reloj,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id, EstadoDeAsistencia $estado): AsistenciaDeEntrenamiento
    {
        $anterior = $this->buscar->ejecutar($id);
        $nueva = $anterior->conEstado($estado, Instante::truncadoASegundos($this->reloj->ahora()));
        $this->asistencias->actualizar($anterior, $nueva);

        return $nueva;
    }
}
