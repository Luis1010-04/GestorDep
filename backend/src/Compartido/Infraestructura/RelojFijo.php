<?php

namespace Src\Compartido\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Src\Compartido\Dominio\Reloj;

/** Reloj detenido en una fecha dada; se usa para controlar el tiempo en pruebas. */
final class RelojFijo implements Reloj
{
    private readonly DateTimeImmutable $momento;

    public function __construct(DateTimeImmutable $momento)
    {
        $this->momento = $momento->setTimezone(new DateTimeZone('UTC'));
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->momento;
    }
}
