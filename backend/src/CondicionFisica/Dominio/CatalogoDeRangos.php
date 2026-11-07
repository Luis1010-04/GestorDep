<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** Rangos vigentes de peso y estatura (DA-16). */
interface CatalogoDeRangos
{
    public function de(string $codigo): RangoDeMedida;

    /** El mayor valor que cabe en la columna de `mediciones_corporales` con ese nombre, según su tipo en la base (AU-09). */
    public function maximoDeColumna(string $codigo): NumeroDecimal;
}
