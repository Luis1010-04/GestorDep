<?php

namespace Src\Consultas\Aplicacion;

use DateTimeImmutable;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/**
 * `posiblemente_desactualizado` (RNF-04, DD-15, DA-98): una captura está posiblemente desactualizada si es anterior a hoy de
 * negocio menos `dias` días; con 30 días, una de hace 31 lo está y una de hace 30 no. El día de la captura se cuenta en la
 * zona de hoy de negocio, no en UTC. Se calcula al leer: no se guarda (`docs/arquitectura.md` §11). Sin captura no hay nada
 * que marcar.
 */
final class MarcaDeDesactualizacion
{
    private readonly string $primerDiaVigente;

    private readonly \DateTimeZone $zona;

    /** @throws ReglaDeNegocioViolada si `dias` es menor que 1 (`dias_de_desactualizacion_invalidos`) */
    public function __construct(DateTimeImmutable $hoyDeNegocio, int $dias)
    {
        if ($dias < 1) {
            throw new ReglaDeNegocioViolada('dias_de_desactualizacion_invalidos');
        }

        $this->primerDiaVigente = $hoyDeNegocio->modify("-{$dias} days")->format('Y-m-d');
        $this->zona = $hoyDeNegocio->getTimezone();
    }

    public function de(?DateTimeImmutable $ultimaCaptura): bool
    {
        return $ultimaCaptura !== null && $ultimaCaptura->setTimezone($this->zona)->format('Y-m-d') < $this->primerDiaVigente;
    }
}
