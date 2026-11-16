<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** El jugador de la vista para convocar: lo mínimo para identificarlo (DA-94). */
final class JugadorConvocable
{
    public function __construct(
        public readonly Identificador $id,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $matricula,
    ) {}
}
