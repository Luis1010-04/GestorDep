<?php

namespace Src\Identidad\Aplicacion;

use Src\Compartido\Dominio\Identificador;

interface CierreDeSesiones
{
    /** Cierra todas las sesiones del usuario salvo la indicada (HU-27). */
    public function cerrarOtras(Identificador $usuario, string $sesionActual): void;
}
