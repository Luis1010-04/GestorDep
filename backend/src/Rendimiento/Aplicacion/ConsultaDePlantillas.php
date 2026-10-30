<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;

/** Plantillas que ve el entrenador de la sesión: las de fábrica y las suyas, nunca las de otro (§5.3). */
interface ConsultaDePlantillas
{
    /**
     * Las de fábrica primero, después por nombre.
     *
     * @return array{elementos: list<PlantillaDeEvaluacion>, total: int}
     */
    public function visiblesDelUsuario(?Identificador $posicion, int $pagina, int $porPagina): array;
}
