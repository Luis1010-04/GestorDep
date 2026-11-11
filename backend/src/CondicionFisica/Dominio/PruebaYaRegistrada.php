<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El entrenador ya registró una prueba de ese tipo para el jugador en el periodo, y no pidió corregirla (DA-04, DA-64). */
final class PruebaYaRegistrada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('prueba_ya_registrada');
    }
}
