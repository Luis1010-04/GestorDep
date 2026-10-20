<?php

namespace Src\Compartido\Dominio;

use DateTimeImmutable;

interface Reloj
{
    /** Fecha y hora actuales en UTC. */
    public function ahora(): DateTimeImmutable;
}
