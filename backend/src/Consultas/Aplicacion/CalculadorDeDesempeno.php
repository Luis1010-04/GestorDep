<?php

namespace Src\Consultas\Aplicacion;

use Src\Compartido\Dominio\NumeroDecimal;

/**
 * Orden por desempeño de los jugadores de un equipo (RN-18, DD-12, DA-95, DA-118). Ordena de mayor a menor el promedio de
 * la ventana de calificaciones que RN-08 ya guardó en la última evaluación (`CalculoDeDesempeno`, de Rendimiento; este
 * calculador no repite esa fórmula, solo ordena). Un jugador sin promedio (datos insuficientes, o sin evaluación) va al
 * final, y los empates conservan el orden de entrada: apellidos, nombres e identificador (DA-118).
 */
final class CalculadorDeDesempeno
{
    /**
     * @template T
     *
     * @param  list<T>  $elementos  en el orden de entrada
     * @param  callable(T): ?NumeroDecimal  $promedio
     * @return list<T>
     */
    public function ordenar(array $elementos, callable $promedio): array
    {
        $ordenados = $elementos;
        // `usort` es estable desde PHP 8.0: los empates conservan el orden de entrada.
        usort($ordenados, function (mixed $a, mixed $b) use ($promedio): int {
            $promedioA = $promedio($a);
            $promedioB = $promedio($b);

            return match (true) {
                $promedioA === null && $promedioB === null => 0,
                $promedioA === null => 1,
                $promedioB === null => -1,
                default => $promedioB->comparar($promedioA),
            };
        });

        return $ordenados;
    }
}
