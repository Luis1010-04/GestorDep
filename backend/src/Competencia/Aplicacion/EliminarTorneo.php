<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Competencia\Dominio\RepositorioDeTorneos;

/** Borrado lógico de el torneo (HU-28): lo que ya lo usa sigue mostrándolo, y el historial guarda la baja. */
final class EliminarTorneo
{
    public function __construct(private readonly RepositorioDeTorneos $torneos) {}

    /** @return bool falso si no existe o ya estaba eliminado */
    public function ejecutar(Identificador $id): bool
    {
        return $this->torneos->eliminar($id);
    }
}
