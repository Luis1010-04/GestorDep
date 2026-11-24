<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Throwable;

/**
 * Serializa una escritura sobre jugadores con su baja (RN-23; AB-20, AB-26, AB-29 y AB-31; DA-139). La baja bloquea la
 * fila del jugador con FOR UPDATE; este puerto la bloquea en modo compartido, así que una escritura que llega durante
 * una baja espera a que termine y entonces ve al jugador en baja. Las escrituras no se bloquean entre sí.
 */
interface CandadoDeJugadores
{
    /**
     * Abre una transacción (o un punto de guardado, si ya hay una), bloquea en modo compartido a los jugadores, en orden de
     * id, y ejecuta `$escritura` si todos siguen sin baja y sin borrar. Si alguno no, lanza lo que devuelva
     * `$siHayInactivos` con esos jugadores y no escribe nada. Cada caso de uso decide su excepción (DA-141). Con la lista
     * vacía (una alineación sin jugadores, por ejemplo) no bloquea nada y solo ejecuta `$escritura` en la transacción.
     *
     * @template T
     *
     * @param  list<Identificador>  $jugadores
     * @param  callable(list<Identificador>): Throwable  $siHayInactivos
     * @param  callable(): T  $escritura
     * @return T
     */
    public function conJugadoresActivos(array $jugadores, callable $siHayInactivos, callable $escritura): mixed;
}
