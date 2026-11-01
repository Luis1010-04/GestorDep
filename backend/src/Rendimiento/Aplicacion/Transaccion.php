<?php

namespace Src\Rendimiento\Aplicacion;

/**
 * Una escritura y la evaluación que provoca van en la misma transacción (§3.3, evaluación persistida). Las
 * transacciones que ya abren los repositorios quedan anidadas dentro.
 */
interface Transaccion
{
    /**
     * @template T
     *
     * @param  callable(): T  $operacion
     * @return T
     */
    public function ejecutar(callable $operacion): mixed;
}
