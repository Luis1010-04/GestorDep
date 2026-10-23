<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Dominio\RepositorioDeCategorias;

/** Borrado lógico de una categoría (HU-28); los equipos que la usan siguen mostrándola. */
final class EliminarCategoria
{
    public function __construct(private readonly RepositorioDeCategorias $categorias) {}

    /** @return bool falso si la categoría no existe o ya estaba eliminada */
    public function ejecutar(Identificador $id): bool
    {
        return $this->categorias->eliminar($id);
    }
}
