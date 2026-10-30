<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Aplicacion\JugadoresDelEquipo;
use Src\Compartido\Dominio\Identificador;

/** Detalle de un entrenamiento con los jugadores del equipo listados (HU-10, criterio 1). */
final class VerEntrenamiento
{
    public function __construct(
        private readonly BuscarEntrenamientoDelEntrenador $buscar,
        private readonly JugadoresDelEquipo $jugadores,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id): EntrenamientoConJugadores
    {
        $entrenamiento = $this->buscar->ejecutar($id);

        return new EntrenamientoConJugadores($entrenamiento, $this->jugadores->de($entrenamiento->equipoId()));
    }
}
