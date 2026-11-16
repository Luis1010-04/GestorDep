<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Una materia del docente en la que está inscrito el jugador, con su asignatura (HU-25). */
final class MateriaDelGrafico
{
    public function __construct(
        public readonly Identificador $id,
        public readonly string $clave,
        public readonly string $nombre,
    ) {}
}
