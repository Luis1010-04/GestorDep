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
     * @param  bool  $incluirBajas  verdadero para consultar; falso para cambiar el umbral, como en la captura (DA-12, DA-28)
     * @return array{entrenador: Identificador, posicion: Identificador}
     *
     * @throws RecursoNoEncontrado
     */
    public function ejecutar(Identificador $equipo, Identificador $jugador, bool $incluirBajas): array
    {
        $entrenador = $this->usuarioActual->identificador();
        if ($entrenador === null || ! $this->equipos->entrena($entrenador, $equipo)) {
            throw new RecursoNoEncontrado;
        }
        if (! $this->visibilidad->esVisibleParaEntrenador($jugador, $incluirBajas)) {
            throw new RecursoNoEncontrado;
        }

        return ['entrenador' => $entrenador, 'posicion' => $this->posiciones->de($jugador, $equipo) ?? throw new RecursoNoEncontrado];
    }
}
