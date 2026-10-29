<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\RepositorioDeAsignaturas;
use Src\Compartido\Dominio\Identificador;

/** Borrado lógico de una asignatura (HU-28); las materias que la usan siguen mostrándola. */
final class EliminarAsignatura
{
    public function __construct(private readonly RepositorioDeAsignaturas $asignaturas) {}

    /** @return bool falso si la asignatura no existe o ya estaba eliminada */
    public function ejecutar(Identificador $id): bool
    {
        return $this->asignaturas->eliminar($id);
    }
}
