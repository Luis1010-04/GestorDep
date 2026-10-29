<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Comprueba que la inscripción exista y sea de una materia del docente (DD-50); ajena o inexistente son indistinguibles. */
final class ComprobarInscripcionDelDocente
{
    public function __construct(private readonly ConsultaDeInscripciones $consulta) {}

    public function ejecutar(Identificador $inscripcion, Identificador $docente): bool
    {
        return $this->consulta->delDocente($inscripcion, $docente) !== null;
    }
}
