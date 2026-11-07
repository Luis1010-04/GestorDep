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

    /** Sale de `information_schema`, no de una constante: `numeric(5,2)` da 999.99 y `numeric(5,1)`, 9999.9. */
    public function maximoDeColumna(string $codigo): NumeroDecimal
    {
        $columna = $this->conexion->table('information_schema.columns')
            ->whereRaw('table_schema = current_schema()')
            ->where('table_name', 'mediciones_corporales')
            ->where('column_name', $codigo)
            ->first(['numeric_precision', 'numeric_scale']);
        if ($columna === null || $columna->numeric_precision === null) {
            throw new RuntimeException('columna_de_medida_desconocida');
        }

        $escala = (int) $columna->numeric_scale;
        $enteros = str_repeat('9', (int) $columna->numeric_precision - $escala);

        return NumeroDecimal::desdeTexto($escala > 0 ? $enteros.'.'.str_repeat('9', $escala) : $enteros);
    }
}
