<?php

namespace Src\Plantel\Aplicacion;

use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeEquipos;
use Src\Plantel\Dominio\RepositorioDeMembresias;
use Src\Plantel\Dominio\RepositorioDePosiciones;

/**
 * Cambia la posición de un jugador en un equipo del entrenador (HU-05). Solo afecta esa membresía y cambiarla
 * en un jugador en baja es válido (RN-17 conserva sus membresías).
 */
final class CambiarPosicionDeJugador
{
    public function __construct(
        private readonly RepositorioDeEquipos $equipos,
        private readonly RepositorioDePosiciones $posiciones,
        private readonly RepositorioDeMembresias $membresias,
        private readonly ConsultaDeJugadores $consulta,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws EquipoNoEncontrado|PosicionInvalida|JugadorNoEncontrado */
    public function ejecutar(Identificador $equipoId, Identificador $jugadorId, string $posicion): JugadorConEquipos
    {
        $entrenador = $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $this->equipos->buscarDelEntrenador($equipoId, $entrenador) ?? throw new EquipoNoEncontrado;
        $posicionId = $this->posiciones->idPorCodigo($posicion) ?? throw new PosicionInvalida;

        $this->membresias->cambiarPosicion($jugadorId, $equipoId, $posicionId);

        return $this->consulta->buscarParaEscribir($jugadorId) ?? throw new JugadorNoEncontrado;
    }
}
