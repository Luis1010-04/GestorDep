<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\Identificador;

/** Tipos de prueba vigentes, con su rango leído de la tabla en cada operación (DA-16). */
interface CatalogoDeTiposDePrueba
{
    /** Nulo si no existe o está eliminado (DA-61). */
    public function buscar(Identificador $id): ?TipoDePrueba;

    /** @return list<TipoDePrueba> por código ascendente (DA-63) */
    public function todos(): array;
}
