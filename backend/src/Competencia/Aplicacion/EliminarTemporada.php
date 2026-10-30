<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\RepositorioDeTemporadas;

/** Borrado lógico de la temporada (HU-28): lo que ya la usa sigue mostrándola, y el historial guarda la baja. */
final class EliminarTemporada
{
    public function __construct(private readonly RepositorioDeTemporadas $temporadas) {}

    /** @return bool falso si no existe o ya estaba eliminada */
    public function ejecutar(Identificador $id): bool
    {
        return $this->temporadas->eliminar($id);
    }
}
