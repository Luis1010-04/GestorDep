<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Dominio\Identificador;

/** Borrado lógico de un periodo (HU-28); las materias que lo usan siguen mostrándolo. */
final class EliminarPeriodo
{
    public function __construct(private readonly RepositorioDePeriodos $periodos) {}

    /** @return bool falso si el periodo no existe o ya estaba eliminado */
    public function ejecutar(Identificador $id): bool
    {
        return $this->periodos->eliminar($id);
    }
}
