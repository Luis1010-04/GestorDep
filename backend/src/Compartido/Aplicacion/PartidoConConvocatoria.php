<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Lo que `ConvocatoriaDePartido` revela de un partido: lo mínimo para capturar rendimiento. */
final class PartidoConConvocatoria
{
    /** @param  list<Identificador>  $convocados  jugadores con convocatoria no eliminada */
    public function __construct(
        public readonly Identificador $id,
        public readonly Identificador $equipoId,
        public readonly bool $jugado,
        public readonly array $convocados,
    ) {}

    public function convoca(Identificador $jugador): bool
    {
        foreach ($this->convocados as $convocado) {
            if ($convocado->igualA($jugador)) {
                return true;
            }
        }

        return false;
    }
}
