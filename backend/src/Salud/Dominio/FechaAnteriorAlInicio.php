<?php

namespace Src\Salud\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La fecha estimada de regreso y la de alta no pueden ser anteriores al inicio de la lesión (DA-17). */
final class FechaAnteriorAlInicio extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('rango_de_fechas_invalido');
    }
}
