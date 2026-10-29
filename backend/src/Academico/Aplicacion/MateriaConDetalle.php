<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\Identificador;

/** Lectura de una materia con los datos de su asignatura y su periodo, que se muestran aunque estén eliminados. */
final class MateriaConDetalle
{
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $asignaturaId,
        public readonly string $asignaturaClave,
        public readonly string $asignaturaNombre,
        public readonly Identificador $periodoId,
        public readonly string $periodoNombre,
        public readonly DateTimeImmutable $actualizadoEn,
    ) {}
}
