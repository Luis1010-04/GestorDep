<?php

namespace Src\Plantel\Aplicacion;

use DateTimeImmutable;
use LogicException;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeJugadores;

/**
 * Actualiza nombres, apellidos y fecha de nacimiento de un jugador del alcance del usuario (HU-05). No toca la matrícula
 * ni los estatus; un jugador en baja también puede actualizarse (ninguna regla lo impide).
 */
final class ActualizarExpedienteDeJugador
{
    public function __construct(
        private readonly ConsultaDeJugadores $consulta,
        private readonly RepositorioDeJugadores $jugadores,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    /** @throws JugadorNoEncontrado */
    public function ejecutar(Identificador $jugadorId, ?string $nombres, ?string $apellidos, ?DateTimeImmutable $fechaNacimiento): JugadorConEquipos
    {
        $this->usuarioActual->identificador() ?? throw new LogicException('usuario_no_identificado');

        $this->consulta->buscar($jugadorId) ?? throw new JugadorNoEncontrado;

        $this->jugadores->actualizarExpediente($jugadorId, $nombres === null ? null : trim($nombres), $apellidos === null ? null : trim($apellidos), $fechaNacimiento);

        return $this->consulta->buscar($jugadorId) ?? throw new JugadorNoEncontrado;
    }
}
