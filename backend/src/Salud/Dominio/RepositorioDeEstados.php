<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Lee y escribe `estados_disponibilidad`; cada escritura lleva su historial en la misma transacción (RNF-02). */
interface RepositorioDeEstados
{
    /** Nulo si el jugador no tiene un estado vigente: apto implícito (DA-01). */
    public function vigente(Identificador $jugadorId): ?EstadoOficial;

    /** Como `vigente`, bloqueando la fila hasta el fin de la transacción. */
    public function vigenteParaActualizar(Identificador $jugadorId): ?EstadoOficial;

    public function guardarNuevo(EstadoOficial $estado): void;

    /** Escribe un cierre o un cambio de fecha; `$anterior` es la instantánea que queda en el historial. */
    public function guardarCambio(EstadoOficial $anterior, EstadoOficial $nuevo): void;
}
