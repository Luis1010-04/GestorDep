<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Aplicacion\EquiposDelEntrenador;
use Src\Compartido\Aplicacion\PosicionEnEquipo;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;

/**
 * Control de pertenencia del umbral, antes de validar el cuerpo (§6.1): el usuario entrena el equipo, el jugador está en
 * su alcance y tiene posición en el equipo. Si falla cualquiera, el mismo 404 que un recurso inexistente.
 */
final class ComprobarJugadorDelEquipo
{
    public function __construct(
        private readonly EquiposDelEntrenador $equipos,
        private readonly VisibilidadDeJugador $visibilidad,
        private readonly PosicionEnEquipo $posiciones,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /**
     * Un jugador en baja sigue visible: ningún requisito impide consultar o cambiar su umbral (F1-H4-FIX).
     *
     * @return array{entrenador: Identificador, posicion: Identificador}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $equipo, Identificador $jugador): array
    {
        $entrenador = $this->usuarioActual->identificador();
        if ($entrenador === null || ! $this->equipos->entrena($entrenador, $equipo)) {
            throw new RecursoNoEncontrado;
        }
        if (! $this->visibilidad->esVisibleParaEntrenador($jugador, incluirBajas: true)) {
            throw new RecursoNoEncontrado;
        }

        return ['entrenador' => $entrenador, 'posicion' => $this->posiciones->de($jugador, $equipo) ?? throw new RecursoNoEncontrado];
    }
}
