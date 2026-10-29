<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de inscripciones con el jugador; toda consulta de jugadores pasa por `AlcanceDeJugadores` (RNF-01). */
interface ConsultaDeInscripciones
{
    public function buscar(Identificador $inscripcionId): ?InscripcionConJugador;

    /**
     * Los alumnos de la materia dentro del alcance del usuario (sin jugadores en baja), por apellidos, nombres e id.
     *
     * @return list<InscripcionConJugador>
     */
    public function listarDeLaMateria(Identificador $materiaId, int $pagina, int $porPagina): array;

    public function contarDeLaMateria(Identificador $materiaId): int;

    /**
     * La inscripción, si es de una materia del docente (DD-50); nula si no existe, está eliminada o es de otra materia.
     * Incluye a los jugadores en baja, marcados (DD-52).
     */
    public function delDocente(Identificador $inscripcionId, Identificador $docenteId): ?InscripcionDelDocente;
}
