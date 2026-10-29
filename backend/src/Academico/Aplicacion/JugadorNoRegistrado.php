<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** No hay un expediente con esa matrícula (HU-06 criterio 3, §6.3). El error no lleva datos de nadie. */
final class JugadorNoRegistrado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_no_registrado');
    }
}
