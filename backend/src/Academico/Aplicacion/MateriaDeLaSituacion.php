<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\CriterioDeAprobacion;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/** El detalle de una materia propia del docente en la situación académica del jugador (DD-48). */
final class MateriaDeLaSituacion
{
    /** @param  list<CalificacionLeida>  $calificaciones */
    public function __construct(
        public readonly Identificador $materiaId,
        public readonly string $clave,
        public readonly string $nombre,
        public readonly ?NumeroDecimal $calificacion,
        public readonly CriterioDeAprobacion $criterio,
        public readonly ?bool $aprobada,
        public readonly array $calificaciones,
    ) {}
}
