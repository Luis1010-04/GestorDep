<?php

namespace Src\Plantel\Aplicacion;

use Src\Plantel\Dominio\Equipo;

/** Lectura de un equipo con el nombre de su categoría. */
final class EquipoConCategoria
{
    public function __construct(
        public readonly Equipo $equipo,
        public readonly string $categoriaNombre,
    ) {}
}
