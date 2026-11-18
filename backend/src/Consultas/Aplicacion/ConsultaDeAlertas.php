<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Alertas abiertas de jugadores, filtradas por lo que el usuario puede ver (HU-22): la académica la ven el docente y el
 * entrenador del jugador (DA-89); las deportivas y de sanciones, los entrenadores del equipo de la alerta (DA-90,
 * DA-102). Una alerta cerrada nunca aparece. No comprueba el alcance sobre el jugador: quien llama ya debe haberlo hecho.
 */
interface ConsultaDeAlertas
{
    /** @return list<AlertaVisible> de la más antigua a la más reciente */
    public function delJugador(Identificador $jugador): array;

    /**
     * @param  list<string>  $jugadores  identificadores de jugadores
     * @return array<string, list<AlertaVisible>> por identificador de jugador; el que no tiene alertas visibles no aparece
     */
    public function deLosJugadores(array $jugadores): array;
}
