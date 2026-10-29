<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\Entrenamiento;
use Src\Competencia\Dominio\RepositorioDeEntrenamientos;

/** Un entrenamiento de un equipo que el usuario entrena; si no existe o es ajeno, 404 (§6.1). */
final class BuscarEntrenamientoDelEntrenador
{
    public function __construct(
        private readonly RepositorioDeEntrenamientos $entrenamientos,
        private readonly ComprobarEquipoEntrenado $comprobarEquipo,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $id): Entrenamiento
    {
        $entrenamiento = $this->entrenamientos->buscar($id) ?? throw new RecursoNoEncontrado;
        $this->comprobarEquipo->ejecutar($entrenamiento->equipoId());

        return $entrenamiento;
    }
}
