<?php

namespace Src\CondicionFisica\Aplicacion;

use Src\CondicionFisica\Dominio\CatalogoDeTiposDePrueba;
use Src\CondicionFisica\Dominio\TipoDePrueba;

/** El catálogo de tipos de prueba, por código ascendente y sin paginar (§6.1, DA-63). */
final class ListarTiposDePrueba
{
    public function __construct(private readonly CatalogoDeTiposDePrueba $catalogo) {}

    /** @return list<TipoDePrueba> */
    public function ejecutar(): array
    {
        return $this->catalogo->todos();
    }
}
