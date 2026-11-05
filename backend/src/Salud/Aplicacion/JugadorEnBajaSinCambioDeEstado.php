<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Con el jugador en baja no se confirma un aviso ni se cambia el estado o la fecha (DA-12). El mensaje es propio (punto 8 del plan de T03). */
final class JugadorEnBajaSinCambioDeEstado extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
