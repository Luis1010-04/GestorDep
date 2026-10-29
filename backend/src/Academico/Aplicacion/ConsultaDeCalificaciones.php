<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de calificaciones no eliminadas. La pertenencia al docente se comprueba por la inscripción (DD-50). */
interface ConsultaDeCalificaciones
{
    public function buscar(Identificador $calificacionId): ?CalificacionLeida;

    /** La inscripción de la calificación; nula si la calificación no existe o está eliminada. */
    public function inscripcionDe(Identificador $calificacionId): ?Identificador;

    /**
     * Los parciales por número y la final al último.
     *
     * @return list<CalificacionLeida>
     */
    public function listarDeLaInscripcion(Identificador $inscripcionId, int $pagina, int $porPagina): array;

    public function contarDeLaInscripcion(Identificador $inscripcionId): int;

    /**
     * Todas las de la inscripción, en el mismo orden que el listado.
     *
     * @return list<CalificacionLeida>
     */
    public function deLaInscripcion(Identificador $inscripcionId): array;
}
