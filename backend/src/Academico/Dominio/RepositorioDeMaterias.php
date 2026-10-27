<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Persistencia de materias. Solo ve las no eliminadas. */
interface RepositorioDeMaterias
{
    /** El mismo docente, asignatura y periodo (HU-06). */
    public function existe(Identificador $asignaturaId, Identificador $periodoId, Identificador $docenteId): bool;

    /**
     * Escribe la materia y su registro `crear` de historial en una sola transacción. Si otra petición registró la misma
     * materia entre la comprobación y la escritura, lanza MateriaDuplicada (Aplicacion).
     */
    public function guardarNueva(Materia $materia): void;
}
