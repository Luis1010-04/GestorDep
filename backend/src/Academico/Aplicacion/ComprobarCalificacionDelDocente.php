<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Devuelve la inscripción de la calificación si es de una materia del docente (DD-50); nula si la calificación no existe o
 * es ajena, sin distinguir entre ambas.
 */
final class ComprobarCalificacionDelDocente
{
    public function __construct(
        private readonly ConsultaDeCalificaciones $calificaciones,
        private readonly ConsultaDeInscripciones $inscripciones,
    ) {}

    public function ejecutar(Identificador $calificacion, Identificador $docente): ?InscripcionDelDocente
    {
        $inscripcion = $this->calificaciones->inscripcionDe($calificacion);

        return $inscripcion === null ? null : $this->inscripciones->delDocente($inscripcion, $docente);
    }
}
