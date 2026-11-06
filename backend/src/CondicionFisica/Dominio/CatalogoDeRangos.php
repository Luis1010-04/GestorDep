<?php

namespace Src\CondicionFisica\Dominio;

/** Rangos vigentes de peso y estatura (DA-16). */
interface CatalogoDeRangos
{
    public function de(string $codigo): RangoDeMedida;
}
