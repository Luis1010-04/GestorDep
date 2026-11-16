<?php

namespace Src\Alertas\Infraestructura\Consola;

use Illuminate\Console\Command;
use Src\Alertas\Aplicacion\RecalcularTodasLasAlertas;

/**
 * `alertas:recalcular` (arquitectura §11, DA-113): recalcula las tres alertas de todos los jugadores sin baja, abre las que
 * se cumplen y cierra las que dejan de cumplirse. No escribe texto en consola; si el recálculo de algún jugador falla,
 * informa la falla al registro de errores, sigue con los demás y sale con 1.
 */
class RecalcularAlertasComando extends Command
{
    protected $signature = 'alertas:recalcular';

    public function handle(RecalcularTodasLasAlertas $recalcular): int
    {
        $resultado = $recalcular->ejecutar();

        foreach ($resultado->fallas as $falla) {
            report($falla);
        }

        return $resultado->huboFallas() ? self::FAILURE : self::SUCCESS;
    }
}
