<?php

namespace Src\Compartido\Dominio;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Los instantes de auditoría se guardan con precisión de segundos (la columna redondea la fracción):
 * se truncan en el origen para que lo guardado y lo devuelto sean el mismo valor.
 */
final class Instante
{
    public static function truncadoASegundos(DateTimeImmutable $instante): DateTimeImmutable
    {
        $utc = $instante->setTimezone(new DateTimeZone('UTC'));

        return $utc->setTime((int) $utc->format('G'), (int) $utc->format('i'), (int) $utc->format('s'));
    }
}
