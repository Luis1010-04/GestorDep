<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Dominio\HoyDeNegocio;
use Src\Compartido\Dominio\Identificador;

/**
 * Periodo vigente (DD-45): el no eliminado cuyo rango contiene el día de negocio; con varios, el de `fecha_inicio`
 * más reciente. Sin ninguno, `null`.
 */
final class PeriodoVigente
{
    public function __construct(
        private readonly RepositorioDePeriodos $periodos,
        private readonly HoyDeNegocio $hoyDeNegocio,
    ) {}

    public function identificador(): ?Identificador
    {
        return $this->periodos->identificadorVigenteEn($this->hoyDeNegocio->hoy());
    }
}
