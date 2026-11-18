<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * La página pedida de los convocados de un partido y el total de los visibles (HU-12 c2, DA-96). `jugadores` va en orden de
 * convocatoria; `total` cuenta lo mismo que la ruta de convocatoria de H3, de modo que las dos coinciden página por página.
 */
final class ConvocatoriaDelPartido
{
    /** @param  list<JugadorConvocable>  $jugadores */
    public function __construct(
        public readonly Identificador $equipo,
        public readonly array $jugadores,
        public readonly int $total,
    ) {}
}
