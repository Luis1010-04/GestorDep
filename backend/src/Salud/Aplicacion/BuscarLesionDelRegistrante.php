<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Salud\Dominio\Lesion;
use Src\Salud\Dominio\RepositorioDeLesiones;

/**
 * La lesión, solo para quien la registró (RN-15) y mientras entrene al jugador (punto 1 del plan de T03). Cualquier otro
 * caso responde el mismo 404 que una lesión inexistente. Admite jugadores en baja (DA-12).
 */
final class BuscarLesionDelRegistrante
{
    public function __construct(
        private readonly RepositorioDeLesiones $lesiones,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws RecursoNoEncontrado */
    public function ejecutar(Identificador $lesionId): Lesion
    {
        $usuario = $this->usuarioActual->identificador() ?? throw new RecursoNoEncontrado;
        $lesion = $this->lesiones->buscar($lesionId);
        if ($lesion === null || ! $lesion->esDe($usuario) || ! $this->visibilidad->esVisibleComoEntrenador($lesion->jugadorId())) {
            throw new RecursoNoEncontrado;
        }

        return $lesion;
    }
}
