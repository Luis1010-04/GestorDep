<?php

namespace Src\Competencia\Aplicacion;

use InvalidArgumentException;
use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\RepositorioDeLugares;

/** Valida el lugar y el responsable que llegan en el alta o el cambio de un entrenamiento. */
final class ReferenciasDeEntrenamiento
{
    public function __construct(
        private readonly RepositorioDeLugares $lugares,
        private readonly EquiposDelEntrenador $equipos,
    ) {}

    /** @throws LugarInexistente si no existe, está eliminado o no es un UUID v7 */
    public function lugar(string $lugarId): Identificador
    {
        $lugar = $this->identificador($lugarId) ?? throw new LugarInexistente;
        if (! $this->lugares->existe($lugar)) {
            throw new LugarInexistente;
        }

        return $lugar;
    }

    /** @throws ResponsableNoAsignado si no entrena el equipo (DA-13) o no es un UUID v7 */
    public function responsable(string $responsableId, Identificador $equipoId): Identificador
    {
        $responsable = $this->identificador($responsableId) ?? throw new ResponsableNoAsignado;
        if (! $this->equipos->entrena($responsable, $equipoId)) {
            throw new ResponsableNoAsignado;
        }

        return $responsable;
    }

    private function identificador(string $texto): ?Identificador
    {
        try {
            return Identificador::desdeTexto($texto);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
