<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * Filtros del listado de estatus (HU-23 c2), combinados con AND. Un id válido que no es de ningún equipo del usuario ni de
 * ninguna materia suya da una lista vacía, no un error (P-05).
 */
final class FiltrosDeEstatus
{
    public function __construct(
        public readonly ?Identificador $equipoId = null,
        public readonly ?Identificador $materiaId = null,
        public readonly ?string $tipoDeAlerta = null,
        /** Verdadero si algún filtro de id vino con un texto que ningún registro puede tener (no es un UUID v7). */
        public readonly bool $sinResultados = false,
    ) {}
}
