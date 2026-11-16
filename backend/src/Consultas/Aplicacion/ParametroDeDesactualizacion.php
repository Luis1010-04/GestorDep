<?php

namespace Src\Consultas\Aplicacion;

/** Días sin captura para marcar un dato como posiblemente desactualizado: el parámetro `desactualizacion` (DA-100, RNF-09). */
interface ParametroDeDesactualizacion
{
    public function dias(): int;
}
