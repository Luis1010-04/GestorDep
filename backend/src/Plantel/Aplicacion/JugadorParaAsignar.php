<?php

namespace Src\Plantel\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lo mínimo que la asignación necesita saber de un jugador hallado por matrícula; nunca sale de la capa de aplicación. */
final class JugadorParaAsignar
{
    public function __construct(
        public readonly Identificador $id,
        public readonly bool $enBaja,
    ) {}
}
