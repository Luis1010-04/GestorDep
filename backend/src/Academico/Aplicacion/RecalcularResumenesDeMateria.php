<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Recalcula el resumen de todos los inscritos de una materia (por ejemplo, tras cambiar su criterio propio, DD-47). */
final class RecalcularResumenesDeMateria
{
    public function __construct(
        private readonly FuenteDeDatosAcademicos $datos,
        private readonly RecalcularResumenDeJugador $recalcular,
    ) {}

    /** @return int jugadores recalculados */
    public function ejecutar(Identificador $materia): int
    {
        $recalculados = 0;
        foreach ($this->datos->jugadoresDeLaMateria($materia) as $jugador) {
            $recalculados += $this->recalcular->ejecutar($jugador) ? 1 : 0;
        }

        return $recalculados;
    }
}
