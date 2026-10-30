<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\RepositorioDeLugares;

/** Borrado lógico de el lugar (HU-28): lo que ya lo usa sigue mostrándolo, y el historial guarda la baja. */
final class EliminarLugar
{
    public function __construct(private readonly RepositorioDeLugares $lugares) {}

    /** @return bool falso si no existe o ya estaba eliminado */
    public function ejecutar(Identificador $id): bool
    {
        return $this->lugares->eliminar($id);
    }
}
