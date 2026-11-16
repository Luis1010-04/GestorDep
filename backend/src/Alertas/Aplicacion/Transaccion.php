<?php

namespace Src\Alertas\Aplicacion;

/** Las tres alertas de un jugador se recalculan en una sola transacción: un fallo no lo deja a medias. */
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
