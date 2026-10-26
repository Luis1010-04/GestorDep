<?php

namespace App\Http\Resources;

use Src\Compartido\Dominio\NumeroDecimal;

/** Los decimales viajan como cadena con dos decimales ("7.50"), nunca como número JSON (DD-22, DD-51). */
final class DecimalComoCadena
{
    public static function de(?NumeroDecimal $numero): ?string
    {
        return $numero?->comoCadena(2);
    }
}
