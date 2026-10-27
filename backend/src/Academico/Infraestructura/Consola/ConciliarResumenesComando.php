<?php

namespace Src\Academico\Infraestructura\Consola;

use Illuminate\Console\Command;
use Src\Academico\Aplicacion\ConciliarResumenes;

/**
 * `alertas:recalcular` (arquitectura §11): concilia los resúmenes académicos de los jugadores con inscripciones. No
 * escribe texto en consola: los mensajes de consola de Académico irán en `lang/es` cuando una tarea lo permita.
 */
class ConciliarResumenesComando extends Command
{
    protected $signature = 'alertas:recalcular';

    public function handle(ConciliarResumenes $conciliar): int
    {
        $conciliar->ejecutar();

        return self::SUCCESS;
    }
}
