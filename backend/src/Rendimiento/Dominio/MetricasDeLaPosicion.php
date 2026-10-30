<?php

namespace Src\Rendimiento\Dominio;

use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Las métricas que se capturan en una posición (HU-14). Valida la lista recibida en este orden (DA-11): cada métrica
 * debe ser de la posición, deben estar todas las obligatorias y cada valor debe estar en el rango de su métrica.
 */
final class MetricasDeLaPosicion
{
    /** @param  list<MetricaDeLaPosicion>  $metricas */
    public function __construct(private readonly array $metricas) {}

    /**
     * @param  list<array{metrica: Identificador, valor: NumeroDecimal}>  $entradas  en el orden de la petición
     * @return list<ValorDeMetrica>
     *
     * @throws MetricaNoAplicaAPosicion|MetricaObligatoriaFaltante|ValorFueraDeRango
     */
    public function valores(array $entradas): array
    {
        $metricas = [];
        foreach ($entradas as $indice => $entrada) {
            $metricas[$indice] = $this->buscar($entrada['metrica']) ?? throw new MetricaNoAplicaAPosicion("metricas.$indice.metrica_id");
        }

        foreach ($this->metricas as $deLaPosicion) {
            if ($deLaPosicion->obligatoria && ! $this->incluye($metricas, $deLaPosicion->metrica)) {
                throw new MetricaObligatoriaFaltante;
            }
        }

        $valores = [];
        foreach ($entradas as $indice => $entrada) {
            if (! $metricas[$indice]->admite($entrada['valor'])) {
                throw new ValorFueraDeRango("metricas.$indice.valor");
            }
            $valores[] = new ValorDeMetrica($entrada['metrica'], $entrada['valor'], $metricas[$indice]->tipo());
        }

        return $valores;
    }

    private function buscar(Identificador $id): ?Metrica
    {
        foreach ($this->metricas as $deLaPosicion) {
            if ($deLaPosicion->metrica->id()->igualA($id)) {
                return $deLaPosicion->metrica;
            }
        }

        return null;
    }

    /** @param  array<int, Metrica>  $metricas */
    private function incluye(array $metricas, Metrica $buscada): bool
    {
        foreach ($metricas as $metrica) {
            if ($metrica->id()->igualA($buscada->id())) {
                return true;
            }
        }

        return false;
    }
}
