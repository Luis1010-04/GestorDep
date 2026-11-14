<?php

namespace Src\CondicionFisica\Aplicacion;

/** Leer con bloqueo y escribir van en la misma transacción. Las que abren los repositorios quedan anidadas dentro. */
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
