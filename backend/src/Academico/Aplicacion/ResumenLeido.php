<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Academico\Dominio\NivelDeRiesgo;
use Src\Compartido\Dominio\NumeroDecimal;

/** Una fila de `resumenes_academicos` de un jugador en un periodo (RN-04, RN-05). */
final class ResumenLeido
{
    public function __construct(
        public readonly ?NumeroDecimal $promedio,
        public readonly int $materiasReprobadas,
        public readonly int $materiasSinCalificar,
        public readonly ?NumeroDecimal $porcentajeAsistencia,
        public readonly ?NivelDeRiesgo $nivelRiesgo,
        public readonly DateTimeImmutable $calculadoEn,
    ) {}
}
