<?php

namespace Src\Rendimiento\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;
use Src\Rendimiento\Aplicacion\ConsultaDeMetricas;
use Src\Rendimiento\Aplicacion\MetricaDelCatalogo;
use Src\Rendimiento\Dominio\CatalogoDeMetricas;
use Src\Rendimiento\Dominio\Metrica;
use Src\Rendimiento\Dominio\MetricaDeLaPosicion;
use Src\Rendimiento\Dominio\MetricasDeLaPosicion;
use Src\Rendimiento\Dominio\TipoDeDato;
use stdClass;

/** Lee `metricas` y `metricas_posicion`: solo las métricas activas y no eliminadas (RNF-09). */
final class CatalogoDeMetricasSql implements CatalogoDeMetricas, ConsultaDeMetricas
{
    public function __construct(private readonly ConnectionInterface $conexion) {}

    public function deLaPosicion(Identificador $posicionId): MetricasDeLaPosicion
    {
        $filas = $this->conexion->table('metricas_posicion')
            ->join('metricas', 'metricas.id', '=', 'metricas_posicion.metrica_id')
            ->where('metricas_posicion.posicion_id', $posicionId->valor())
            ->where('metricas.activa', true)
            ->whereNull('metricas.eliminado_en')
            ->orderBy('metricas_posicion.orden')
            ->get(['metricas.id', 'metricas.tipo_dato', 'metricas.minimo', 'metricas.maximo', 'metricas_posicion.obligatoria']);

        return new MetricasDeLaPosicion(array_values($filas->map(fn (stdClass $fila) => new MetricaDeLaPosicion(
            new Metrica(Identificador::desdeTexto($fila->id), TipoDeDato::from($fila->tipo_dato), NumeroDecimal::desdeTexto($fila->minimo), NumeroDecimal::desdeTexto($fila->maximo)),
            $fila->obligatoria,
        ))->all()));
    }

    public function activas(): array
    {
        $posiciones = $this->conexion->table('metricas_posicion')
            ->orderBy('orden')
            ->orderBy('posicion_id')
            ->get(['metrica_id', 'posicion_id', 'obligatoria', 'orden'])
            ->groupBy('metrica_id');

        $metricas = $this->conexion->table('metricas')
            ->where('activa', true)
            ->whereNull('eliminado_en')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tipo_dato', 'minimo', 'maximo'])
            ->map(fn (stdClass $fila) => new MetricaDelCatalogo(
                Identificador::desdeTexto($fila->id),
                $fila->codigo,
                $fila->nombre,
                TipoDeDato::from($fila->tipo_dato),
                NumeroDecimal::desdeTexto($fila->minimo),
                NumeroDecimal::desdeTexto($fila->maximo),
                array_values(($posiciones[$fila->id] ?? collect())->map(fn (stdClass $posicion) => [
                    'posicion_id' => Identificador::desdeTexto($posicion->posicion_id),
                    'obligatoria' => $posicion->obligatoria,
                    'orden' => $posicion->orden,
                ])->all()),
            ))
            ->all();

        return array_values($metricas);
    }
}
