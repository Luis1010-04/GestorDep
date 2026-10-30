<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\RepositorioDeRivales;

/** Borrado lógico de el rival (HU-28): lo que ya lo usa sigue mostrándolo, y el historial guarda la baja. */
final class EliminarRival
{
    public function __construct(private readonly RepositorioDeRivales $rivales) {}

    /** @return bool falso si no existe o ya estaba eliminado */
    public function ejecutar(Identificador $id): bool
    {
        return $this->rivales->eliminar($id);
    }
}
