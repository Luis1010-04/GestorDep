<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Listado de estatus de los jugadores del usuario (HU-23). Vive en Consultas porque cruza Plantel, Académico, Salud y
 * Alertas (docs/arquitectura.md §4.3). Toda lectura pasa por `AlcanceDeJugadores` y excluye a los jugadores en baja (RN-23).
 */
interface ConsultaDeEstatus
{
    /**
     * @return array{elementos: list<FilaDeEstatus>, total: int} ordenado por apellidos, nombres e identificador
     */
    public function pagina(FiltrosDeEstatus $filtros, int $pagina, int $porPagina): array;

    /** Verdadero si el jugador está en el alcance del usuario y no está en baja. */
    public function jugadorVisible(Identificador $jugador): bool;
}
