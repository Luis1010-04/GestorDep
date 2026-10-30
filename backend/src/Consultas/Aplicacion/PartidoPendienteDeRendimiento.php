<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Un partido pendiente de captura y cuántos de sus convocados visibles no tienen rendimiento. */
final class PartidoPendienteDeRendimiento
{
    public function __construct(
        public readonly Identificador $partidoId,
        public readonly Identificador $equipoId,
        public readonly DateTimeImmutable $iniciaEn,
        public readonly int $convocadosSinRendimiento,
    ) {}
}
