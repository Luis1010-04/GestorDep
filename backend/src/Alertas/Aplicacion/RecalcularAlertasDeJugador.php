<?php

namespace Src\Alertas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Las alertas de un jugador: las dos deportivas de cada uno de sus equipos y la académica, en una sola transacción. */
final class RecalcularAlertasDeJugador
{
    public function __construct(
        private readonly Transaccion $transaccion,
        private readonly RecalcularAlertasDeportivasDeMembresia $deportivas,
        private readonly RecalcularAlertaAcademicaDeJugador $academica,
    ) {}

    /** @param  list<Identificador>  $equipos  los equipos con membresía vigente; puede estar vacía */
    public function ejecutar(Identificador $jugador, array $equipos, DateTimeImmutable $ahora, DateTimeImmutable $hoy): void
    {
        $this->transaccion->ejecutar(function () use ($jugador, $equipos, $ahora, $hoy): void {
            foreach ($equipos as $equipo) {
                $this->deportivas->ejecutar($jugador, $equipo, $ahora, $hoy);
            }

            $this->academica->ejecutar($jugador, $ahora);
        });
    }
}
