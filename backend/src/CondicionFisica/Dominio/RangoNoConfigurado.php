<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Falta la fila del rango de una medida: un fallo de datos de fábrica, no del servidor (DA-70). Responde 422. */
final class RangoNoConfigurado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('rango_no_configurado');
    }
}
