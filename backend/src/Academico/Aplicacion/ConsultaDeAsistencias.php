<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lecturas de asistencias a clase no eliminadas. La pertenencia al docente se comprueba por la inscripción (DD-50). */
interface ConsultaDeAsistencias
{
    public function buscar(Identificador $asistenciaId): ?AsistenciaLeida;

    /** La inscripción de la asistencia; nula si la asistencia no existe o está eliminada. */
    public function inscripcionDe(Identificador $asistenciaId): ?Identificador;

    /**
     * Por fecha descendente.
     *
     * @return list<AsistenciaLeida>
     */
    public function listarDeLaInscripcion(Identificador $inscripcionId, int $pagina, int $porPagina): array;

    public function contarDeLaInscripcion(Identificador $inscripcionId): int;
}
