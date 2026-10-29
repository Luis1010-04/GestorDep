<?php

namespace Src\Academico\Dominio;

use Src\Compartido\Dominio\NumeroDecimal;

/** La escala de calificaciones: de 0 a 10, ambos extremos incluidos (RN-01; coincide con los CHECK de la base). */
final class EscalaDeCalificaciones
{
    private const MINIMA = '0';

    private const MAXIMA = '10';

    public static function contiene(NumeroDecimal $valor): bool
    {
        return $valor->mayorOIgualQue(NumeroDecimal::desdeTexto(self::MINIMA))
            && $valor->menorOIgualQue(NumeroDecimal::desdeTexto(self::MAXIMA));
    }
}
