<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Una inscripción en una materia del docente, con lo que la captura necesita: a quién publicar y si el jugador está en baja. */
final class InscripcionDelDocente
{
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $materiaId,
        public readonly Identificador $jugadorId,
        public readonly bool $jugadorEnBaja,
    ) {}
}
