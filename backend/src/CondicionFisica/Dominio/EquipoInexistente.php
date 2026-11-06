<?php

namespace Src\CondicionFisica\Dominio;

/** Equipo inexistente, eliminado, ajeno o sin el jugador: el mismo cuerpo en todos los casos (DA-13). */
final class EquipoInexistente extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('equipo_inexistente', 'equipo_id');
    }
}
