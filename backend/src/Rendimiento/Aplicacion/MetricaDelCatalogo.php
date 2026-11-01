<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Dominio\TipoDeDato;

/** Una métrica del catálogo con las posiciones en que se captura (sin nombres de posición: Rendimiento no lee Plantel). */
final class MetricaDelCatalogo
{
    /** @param  list<array{posicion_id: Identificador, obligatoria: bool, orden: int}>  $posiciones  por orden */
    public function __construct(
        public readonly Identificador $id,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly TipoDeDato $tipo,
        public readonly NumeroDecimal $minimo,
        public readonly NumeroDecimal $maximo,
        public readonly array $posiciones,
    ) {}
}
