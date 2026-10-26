<?php

namespace Src\Plantel\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de la membresía de un jugador en un equipo (`jugador_equipo`), con su historial. */
interface RepositorioDeMembresias
{
    /**
     * Crea la membresía vigente (`desde` es hoy en la zona de la institución) y su historial `crear`, en una transacción.
     * Si ya hay una vigente en ese equipo, lanza JugadorYaEnEquipo (Aplicacion).
     */
    public function asignar(Identificador $jugadorId, Identificador $equipoId, Identificador $posicionId): void;

    /**
     * Cambia la posición de la membresía vigente y escribe su historial `actualizar` con la fila completa antes y después,
     * en una transacción. Sin diferencia real no escribe nada. Si no hay membresía vigente, lanza JugadorNoEncontrado (Aplicacion).
     */
    public function cambiarPosicion(Identificador $jugadorId, Identificador $equipoId, Identificador $posicionId): void;
}
