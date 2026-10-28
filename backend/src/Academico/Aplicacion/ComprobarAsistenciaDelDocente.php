<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Devuelve la inscripción de la asistencia si es de una materia del docente (DD-50); nula si la asistencia no existe o es
 * ajena, sin distinguir entre ambas.
 */
final class ComprobarAsistenciaDelDocente
{
    public function __construct(
        private readonly ConsultaDeAsistencias $asistencias,
        private readonly ConsultaDeInscripciones $inscripciones,
    ) {}

    public function ejecutar(Identificador $asistencia, Identificador $docente): ?InscripcionDelDocente
    {
        $inscripcion = $this->asistencias->inscripcionDe($asistencia);

        return $inscripcion === null ? null : $this->inscripciones->delDocente($inscripcion, $docente);
    }
}
