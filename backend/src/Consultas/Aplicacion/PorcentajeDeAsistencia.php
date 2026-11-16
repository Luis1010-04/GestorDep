<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\NumeroDecimal;

/** Un porcentaje de asistencia y la fecha de la última actualización de los registros que lo forman; nulos sin registros. */
final class PorcentajeDeAsistencia
{
    public function __construct(
        public readonly ?NumeroDecimal $porcentaje,
        public readonly ?DateTimeImmutable $actualizadoEn,
    ) {}
}
