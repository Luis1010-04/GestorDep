<?php

namespace Src\Academico\Infraestructura\Escuchas;

use Illuminate\Contracts\Queue\ShouldQueue;
use Src\Academico\Aplicacion\RecalcularResumenDeJugador;
use Src\Academico\Aplicacion\RecalcularResumenesDeMateria;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Src\Compartido\Dominio\Identificador;

/**
 * Recalcula `resumenes_academicos` al ocurrir un evento académico (arquitectura §11): en cola, después del commit,
 * idempotente (guarda con upsert) y reintentable (3 intentos).
 */
final class RecalcularResumenAcademico implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function __construct(
        private readonly RecalcularResumenDeJugador $recalcularJugador,
        private readonly RecalcularResumenesDeMateria $recalcularMateria,
    ) {}

    public function handle(CalificacionRegistrada|AsistenciaDeClaseRegistrada|JugadorInscrito|CriterioDeMateriaModificado $evento): void
    {
        if ($evento instanceof CriterioDeMateriaModificado) {
            $this->recalcularMateria->ejecutar(Identificador::desdeTexto($evento->materiaId));

            return;
        }

        $this->recalcularJugador->ejecutar(Identificador::desdeTexto($evento->jugadorId));
    }
}
