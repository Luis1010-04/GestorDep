<?php

namespace Src\Alertas\Infraestructura\Escuchas;

use Illuminate\Contracts\Queue\ShouldQueue;
use Src\Alertas\Aplicacion\RecalcularAlertaAcademicaDeJugador;
use Src\Compartido\Aplicacion\JugadoresDeLaMateria;
use Src\Compartido\Aplicacion\MembresiasVigentes;
use Src\Compartido\Dominio\Eventos\AsistenciaDeClaseRegistrada;
use Src\Compartido\Dominio\Eventos\CalificacionRegistrada;
use Src\Compartido\Dominio\Eventos\CriterioDeMateriaModificado;
use Src\Compartido\Dominio\Eventos\JugadorInscrito;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Recalcula la alerta de riesgo de baja académica al ocurrir un evento de Académico (DA-88, arquitectura §11): en cola,
 * después del commit, idempotente (decide con lo que hay en la base y abre o cierra una sola vez) y reintentable (3
 * intentos). El nivel sale del puerto de P-09, no del resumen guardado, para no depender del escucha del resumen. A un
 * jugador en baja no se le abren ni se le cierran alertas (P-08c, DA-99).
 */
final class RecalcularAlertaAcademica implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function __construct(
        private readonly RecalcularAlertaAcademicaDeJugador $recalcular,
        private readonly JugadoresDeLaMateria $jugadoresDeLaMateria,
        private readonly MembresiasVigentes $membresias,
        private readonly Reloj $reloj,
    ) {}

    public function handle(CalificacionRegistrada|AsistenciaDeClaseRegistrada|JugadorInscrito|CriterioDeMateriaModificado $evento): void
    {
        $jugadores = $evento instanceof CriterioDeMateriaModificado
            ? $this->jugadoresDeLaMateria->de(Identificador::desdeTexto($evento->materiaId))
            : [Identificador::desdeTexto($evento->jugadorId)];
        $ahora = Instante::truncadoASegundos($this->reloj->ahora());

        foreach ($jugadores as $jugador) {
            if ($this->membresias->delJugador($jugador) !== []) {
                $this->recalcular->ejecutar($jugador, $ahora);
            }
        }
    }
}
