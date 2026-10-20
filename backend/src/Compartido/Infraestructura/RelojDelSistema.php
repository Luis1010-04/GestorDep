<?php

namespace Src\Compartido\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Src\Compartido\Dominio\Reloj;

final class RelojDelSistema implements Reloj
{
    public function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
