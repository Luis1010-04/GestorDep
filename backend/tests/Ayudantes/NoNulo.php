<?php

namespace Tests\Ayudantes;

use PHPUnit\Framework\AssertionFailedError;

/**
 * F1-H5-T00 (docs/reportes/f1-h5-plan-t00.md): comprobación de nulos para las pruebas en PHPStan nivel 8. Se envuelve
 * el punto de origen de un valor que la prueba ya da por existente (una fila recién creada, un promedio con datos):
 * si es nulo, la prueba falla con un mensaje que dice qué faltaba; si no, se devuelve con su tipo sin nulo. Un valor
 * que alguna prueba espera nulo (DD-43, DD-44) no se envuelve.
 */
final class NoNulo
{
    /**
     * @template T
     *
     * @param  T|null  $valor
     * @return T
     */
    public static function exigir(mixed $valor, string $que): mixed
    {
        if ($valor === null) {
            throw new AssertionFailedError("Se esperaba un valor y se obtuvo nulo: {$que}.");
        }

        return $valor;
    }
}
