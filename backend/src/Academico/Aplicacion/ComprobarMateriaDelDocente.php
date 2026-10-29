<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Comprueba que la materia exista y sea del docente (DD-50); ajena o inexistente son indistinguibles. */
final class ComprobarMateriaDelDocente
{
    public function __construct(private readonly ConsultaDeMaterias $consulta) {}

    public function ejecutar(Identificador $materia, Identificador $docente): bool
    {
        return $this->consulta->buscarDelDocente($materia, $docente) !== null;
    }
}
