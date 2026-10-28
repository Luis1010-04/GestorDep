<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Una materia del docente en la que el jugador está inscrito, con su inscripción y su asignatura (aunque esté eliminada). */
final class MateriaDelDocente
{
    public function __construct(
        public readonly Identificador $materiaId,
        public readonly Identificador $inscripcionId,
        public readonly string $clave,
        public readonly string $nombre,
    ) {}
}
