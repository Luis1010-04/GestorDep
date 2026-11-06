<?php

namespace Src\CondicionFisica\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use RuntimeException;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\CondicionFisica\Dominio\CatalogoDeRangos;
use Src\CondicionFisica\Dominio\RangoDeMedida;

/** Lee `rangos_mediciones_corporales` en cada operación. Un rango faltante no se resuelve con un valor del código (DA-16). */
final class CatalogoDeRangosSql implements CatalogoDeRangos
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function de(string $codigo): RangoDeMedida
    {
        $fila = $this->conexion->table('rangos_mediciones_corporales')->where('codigo', $codigo)->whereNull('eliminado_en')->first(['minimo', 'maximo']);
        if ($fila === null) {
            throw new RuntimeException('rango_de_medida_faltante');
        }

        return new RangoDeMedida(NumeroDecimal::desdeTexto($fila->minimo), NumeroDecimal::desdeTexto($fila->maximo));
    }
}
