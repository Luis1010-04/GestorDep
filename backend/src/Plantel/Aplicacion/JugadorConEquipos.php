<?php

namespace Src\Plantel\Aplicacion;

use DateTimeImmutable;

/** Lectura de un jugador visible para el usuario; `equipos` solo trae los equipos de ese usuario (RN-14). */
final class JugadorConEquipos
{
    /** @param  list<EquipoDelJugador>  $equipos */
    public function __construct(
        public readonly string $id,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $fechaNacimiento,
        public readonly string $matricula,
        public readonly string $estatusAcademico,
        public readonly string $estatusDeportivo,
        public readonly array $equipos,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}
}
