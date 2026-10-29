<?php

namespace Src\Competencia\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\ConteoDeAsistenciaAEntrenamientos;

/**
 * Porcentaje de asistencia de un jugador visible para el entrenador (DA-20), solo con los entrenamientos de los
 * equipos que él entrena: un jugador en dos equipos no revela la asistencia del otro (§3.4).
 */
final class VerAsistenciaDelJugador
{
    public function __construct(
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly EquiposDelEntrenador $equipos,
        private readonly ConsultaDeAsistencia $consulta,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $jugadorId): ConteoDeAsistenciaAEntrenamientos
    {
        if (! $this->visibilidad->esVisibleParaEntrenador($jugadorId)) {
            throw new RecursoNoEncontrado;
        }
        $usuario = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        return $this->consulta->delJugadorEnEquipos($jugadorId, $this->equipos->equiposDe($usuario));
    }
}
