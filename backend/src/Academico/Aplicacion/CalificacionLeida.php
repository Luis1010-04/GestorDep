<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Academico\Dominio\TipoDeCalificacion;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** Lectura de una calificación con el criterio vigente al capturarla (DD-47). */
final class CalificacionLeida
{
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $inscripcionId,
        public readonly TipoDeCalificacion $tipo,
        public readonly ?int $numeroParcial,
        public readonly NumeroDecimal $valor,
        public readonly CriterioDeAprobacion $criterio,
        public readonly DateTimeImmutable $fecha,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}
}
