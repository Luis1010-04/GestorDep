<?php

namespace Src\Plantel\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Consulta de las posiciones de fábrica (portero, defensa, medio, delantero). */
interface RepositorioDePosiciones
{
    public function idPorCodigo(string $codigo): ?Identificador;
}
