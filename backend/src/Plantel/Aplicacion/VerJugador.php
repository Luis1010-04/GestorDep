<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Detalle de un jugador del alcance del usuario; también lo devuelve en baja (DD-30). */
final class VerJugador
{
    public function __construct(private readonly ConsultaDeJugadores $jugadores) {}

    /** @throws JugadorNoEncontrado */
    public function ejecutar(Identificador $jugadorId): JugadorConEquipos
    {
        return $this->jugadores->buscar($jugadorId) ?? throw new JugadorNoEncontrado;
    }
}
