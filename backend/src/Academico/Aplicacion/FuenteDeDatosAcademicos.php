<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\ConteoDeAsistencia;
use Src\Academico\Dominio\MateriaEvaluada;
use Src\Compartido\Dominio\Identificador;

/**
 * Lee lo capturado para calcular el resumen de un jugador (RN-02, RN-03, RN-05). Arma los objetos del dominio de T04:
 * la calificación de cada materia y el criterio vigente que le toca (el propio o, si no hay, el general).
 */
interface FuenteDeDatosAcademicos
{
    /**
     * Las materias del jugador en el periodo (inscripciones y materias no eliminadas).
     *
     * @return list<MateriaEvaluada>
     */
    public function materiasEvaluadas(Identificador $jugador, Identificador $periodo): array;

    /** Los registros de asistencia del jugador en todas sus materias del periodo (RN-05). */
    public function conteoDeAsistencia(Identificador $jugador, Identificador $periodo): ConteoDeAsistencia;

    /**
     * Jugadores con inscripción vigente en la materia.
     *
     * @return list<Identificador>
     */
    public function jugadoresDeLaMateria(Identificador $materia): array;

    /**
     * Todos los jugadores con alguna inscripción vigente, en cualquier periodo.
     *
     * @return list<Identificador>
     */
    public function jugadoresConInscripciones(): array;
}
