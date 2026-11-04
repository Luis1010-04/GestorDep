<?php

namespace Src\Salud\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** No se crean avisos de un jugador en baja (DD-60). Mismo código que H1 y H2; su mensaje es propio de los avisos (DA-27). */
final class JugadorEnBajaSinAviso extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('jugador_en_baja');
    }
}
