<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Lecturas de la vista para convocar (HU-24, DA-94). Vive en Consultas porque cruza Plantel, Rendimiento, Académico, Salud,
 * Competencia y Condición física (docs/arquitectura.md §4.3). Los jugadores salen de `AlcanceDeJugadores`, solo con la rama
 * del entrenador y sin bajas (RN-23); lo que registra un entrenador sale solo de lo que registró el usuario (RN-14). La
 * evaluación se lee de `evaluaciones_rendimiento` (DA-120): no se recalcula aquí.
 */
interface ConsultaDeVistaParaConvocar
{
    /** Nulo si el jugador no existe, no está en el alcance del usuario como entrenador o está en baja. */
    public function jugador(Identificador $jugador): ?JugadorConvocable;

    /** Verdadero si el usuario entrena el equipo y el jugador tiene en él una membresía vigente. */
    public function jugadorEnEquipoDelUsuario(Identificador $jugador, Identificador $equipo): bool;

    /**
     * @return list<JugadorConvocable>|null los jugadores con membresía vigente en el equipo, sin bajas, ordenados por apellidos,
     *                                      nombres e identificador (DA-118); nulo si el usuario no entrena el equipo
     */
    public function jugadoresDelEquipo(Identificador $equipo): ?array;

    /**
     * Los convocados de un partido (HU-12 c2, DA-96), con la misma visibilidad que la ruta de convocatoria de H3: en orden de
     * convocatoria, por `AlcanceDeJugadores` solo con la rama del entrenador y sin bajas, de modo que el total cuenta igual.
     * Nulo si el partido no existe, está eliminado o es de un equipo que el usuario no entrena.
     */
    public function convocatoriaDelPartido(Identificador $partido, int $pagina, int $porPagina): ?ConvocatoriaDelPartido;

    /** El equipo de la evaluación más reciente del usuario para el jugador (DA-119). */
    public function equipoDeLaUltimaEvaluacion(Identificador $jugador): ?Identificador;

    /** La última evaluación del usuario para el jugador en el equipo; nula si no hay ninguna. */
    public function evaluacionReciente(Identificador $jugador, Identificador $equipo): ?EvaluacionReciente;

    public function disponibilidad(Identificador $jugador): DatosDeDisponibilidad;

    public function situacionAcademica(Identificador $jugador): DatosDeSituacionAcademica;

    public function asistencia(Identificador $jugador): DatosDeAsistencia;

    public function estadoFisico(Identificador $jugador): DatosDeEstadoFisico;
}
