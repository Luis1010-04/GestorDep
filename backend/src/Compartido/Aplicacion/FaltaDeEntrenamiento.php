<?php

namespace Src\Compartido\Aplicacion;

use DateTimeImmutable;

/** Una falta de un jugador a un entrenamiento realizado, con la fecha del entrenamiento en la zona de negocio (DA-91). */
final class FaltaDeEntrenamiento
{
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly bool $justificada,
    ) {}
}
