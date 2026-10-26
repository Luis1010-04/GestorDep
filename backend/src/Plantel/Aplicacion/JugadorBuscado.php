<?php

namespace Src\Plantel\Aplicacion;

/** Resultado de la búsqueda por matrícula (RN-20): solo nombres, apellidos y matrícula, nunca el identificador. */
final class JugadorBuscado
{
    public function __construct(
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $matricula,
    ) {}
}
