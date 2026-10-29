<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Comprueba que el jugador esté en el alcance de **entrenador** del usuario antes de escribir (DA-10). Ajeno,
 * inexistente o visible solo porque el usuario lo enseña: `JugadorNoEncontrado` (404).
 */
final class ComprobarJugadorEscribible
{
    public function __construct(private readonly ConsultaDeJugadores $jugadores) {}

    /** @throws JugadorNoEncontrado */
    public function ejecutar(Identificador $jugadorId): void
    {
        $this->jugadores->buscarParaEscribir($jugadorId) ?? throw new JugadorNoEncontrado;
    }
}
