<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de la situación académica de un jugador. La visibilidad del jugador pasa por `AlcanceDeJugadores` (RNF-01). */
interface ConsultaDeSituacionAcademica
{
    /** Si el jugador está en el alcance del usuario actual, incluidos los que están en baja (DD-30, DD-52). */
    public function jugadorVisible(Identificador $jugadorId): bool;

    public function nombreDelPeriodo(Identificador $periodoId): string;

    public function resumen(Identificador $jugadorId, Identificador $periodoId): ?ResumenLeido;

    /** Si el jugador tiene una inscripción vigente en alguna materia vigente del docente, en cualquier periodo (§5.4). */
    public function esDocenteDelJugador(Identificador $jugadorId, Identificador $docenteId): bool;

    /**
     * Las materias del docente en el periodo en las que el jugador está inscrito, por clave de asignatura.
     *
     * @return list<MateriaDelDocente>
     */
    public function materiasDelDocente(Identificador $jugadorId, Identificador $docenteId, Identificador $periodoId): array;
}
