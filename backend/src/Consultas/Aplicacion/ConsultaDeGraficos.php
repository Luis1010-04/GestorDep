<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Los datos de los gráficos de historial (HU-25) que no son un porcentaje de asistencia: esos los dan los puertos de
 * Compartido (DA-109). Todo lo que registra un entrenador sale solo de lo que registró el usuario que consulta (RN-14,
 * DA-97). No comprueba el alcance sobre el jugador ni sobre el equipo: quien llama ya debe haberlo hecho.
 */
interface ConsultaDeGraficos
{
    /** Calificaciones generales de partido que registró el usuario, por fecha del partido; de un equipo si se indica. */
    public function rendimientoDelJugador(Identificador $jugador, ?Identificador $equipo = null): DatosDeSerie;

    /** El promedio por partido de lo que registró el usuario en los partidos del equipo, con dos decimales. */
    public function rendimientoDelEquipo(Identificador $equipo): DatosDeSerie;

    /**
     * Las mediciones corporales que registró el usuario, por periodo `AAAA-MM`.
     *
     * @return array{peso_kg: DatosDeSerie, estatura_cm: DatosDeSerie, imc: DatosDeSerie}
     */
    public function medicionesDelJugador(Identificador $jugador): array;

    /** El promedio del resumen académico de cada periodo escolar, del más antiguo al más reciente. */
    public function resumenAcademicoDelJugador(Identificador $jugador): DatosDeSerie;

    /**
     * Las materias del usuario como docente en las que está inscrito el jugador, de cualquier periodo.
     *
     * @return list<MateriaDelGrafico>
     */
    public function materiasDelDocente(Identificador $jugador): array;

    /** Las calificaciones del jugador en una materia del usuario, por fecha de captura. */
    public function calificacionesDeLaMateria(Identificador $jugador, Identificador $materia): DatosDeSerie;

    /**
     * Verdadero si el jugador tiene una membresía vigente en alguno de los equipos.
     *
     * @param  list<Identificador>  $equipos
     */
    public function jugadorEnEquipos(Identificador $jugador, array $equipos): bool;
}
