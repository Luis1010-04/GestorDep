<?php

namespace Src\Alertas\Aplicacion;

use Src\Compartido\Aplicacion\MembresiasVigentes;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Throwable;

/**
 * Lo que ejecuta `alertas:recalcular` (DA-113): recalcula las alertas de todos los jugadores sin baja. La fecha de cálculo
 * se toma una sola vez, al empezar, del reloj inyectable. Si el recálculo de un jugador falla, la corrida sigue con los
 * demás y devuelve la falla (el comando la informa).
 */
final class RecalcularTodasLasAlertas
{
    public function __construct(
        private readonly MembresiasVigentes $membresias,
        private readonly RecalcularAlertasDeJugador $recalcularJugador,
        private readonly Reloj $reloj,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function ejecutar(): ResultadoDelRecalculo
    {
        $ahora = Instante::truncadoASegundos($this->reloj->ahora());
        $hoy = $this->hoyDeNegocio->hoy();

        /** @var array<string, list<Identificador>> $equiposPorJugador */
        $equiposPorJugador = [];
        foreach ($this->membresias->todas() as $membresia) {
            $equiposPorJugador[$membresia->jugadorId] ??= [];
            if ($membresia->equipoId !== null) {
                $equiposPorJugador[$membresia->jugadorId][] = Identificador::desdeTexto($membresia->equipoId);
            }
        }

        $recalculados = 0;
        $fallas = [];
        foreach ($equiposPorJugador as $jugador => $equipos) {
            try {
                $this->recalcularJugador->ejecutar(Identificador::desdeTexto((string) $jugador), $equipos, $ahora, $hoy);
                $recalculados++;
            } catch (Throwable $falla) {
                $fallas[] = $falla;
            }
        }

        return new ResultadoDelRecalculo($recalculados, $fallas);
    }
}
