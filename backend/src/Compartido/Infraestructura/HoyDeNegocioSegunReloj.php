<?php

namespace Src\Compartido\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Reloj;

/** Deduce el día de negocio del Reloj convertido a la zona configurada (`app.zona_presentacion`). */
final class HoyDeNegocioSegunReloj implements HoyDeNegocio
{
    public function __construct(
        private readonly Reloj $reloj,
        private readonly string $zona,
    ) {}

    public function hoy(): DateTimeImmutable
    {
        return $this->reloj->ahora()->setTimezone(new DateTimeZone($this->zona))->setTime(0, 0);
    }
}
