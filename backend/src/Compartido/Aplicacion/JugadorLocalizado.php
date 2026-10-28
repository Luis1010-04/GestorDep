<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lo único que `LocalizadorDeJugadores` revela de un jugador: su identificador y si está en baja. Nada más (RN-20). */
final class JugadorLocalizado
{
    public function __construct(
        public readonly Identificador $id,
        public readonly bool $enBaja,
    ) {}
}
