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
}
