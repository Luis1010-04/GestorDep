<?php

namespace Src\Academico\Aplicacion;

/**
 * Concilia los resúmenes de todos los jugadores con inscripciones (arquitectura §11, `alertas:recalcular`): útil tras
 * cambiar parámetros de fábrica o al cambiar el día vigente. Idempotente.
 */
final class ConciliarResumenes
{
    public function __construct(
        private readonly FuenteDeDatosAcademicos $datos,
        private readonly RecalcularResumenDeJugador $recalcular,
    ) {}

    /** @return int jugadores recalculados */
    public function ejecutar(): int
    {
        $recalculados = 0;
        foreach ($this->datos->jugadoresConInscripciones() as $jugador) {
            $recalculados += $this->recalcular->ejecutar($jugador) ? 1 : 0;
        }

        return $recalculados;
    }
}
