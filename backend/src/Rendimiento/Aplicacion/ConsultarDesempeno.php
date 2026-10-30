<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\EvaluacionDeRendimiento;
use Src\Rendimiento\Dominio\RepositorioDeEvaluaciones;

/**
 * Desempeño de un jugador en un equipo para el entrenador de la sesión (HU-16): la última evaluación guardada o, si no
 * hay ninguna, la de este instante sin guardarla (§3.3, v1.0.5).
 */
final class ConsultarDesempeno
{
    public function __construct(
        private readonly ComprobarJugadorDelEquipo $comprobar,
        private readonly RepositorioDeEvaluaciones $evaluaciones,
        private readonly RecalcularEvaluacion $recalcular,
    ) {}

    /** @throws RecursoNoEncontrado|SinUmbralAplicable si no hay umbral propio ni plantilla de fábrica de la posición (DA-33) */
    public function ejecutar(Identificador $equipo, Identificador $jugador): EvaluacionDeRendimiento
    {
        ['entrenador' => $entrenador] = $this->comprobar->ejecutar($equipo, $jugador);

        return $this->evaluaciones->ultimaDe($jugador, $equipo, $entrenador)
            ?? $this->recalcular->calcular($jugador, $equipo, $entrenador)
            ?? throw new SinUmbralAplicable;
    }
}
