<?php

namespace Src\Academico\Aplicacion;

use DateTimeImmutable;
use Src\Academico\Dominio\PeriodoEscolar;
use Src\Academico\Dominio\RepositorioDePeriodos;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Compartido\Dominio\Reloj;

/** Alta de un periodo escolar (HU-28). */
final class CrearPeriodo
{
    public function __construct(
        private readonly RepositorioDePeriodos $periodos,
        private readonly PeriodoVigente $periodoVigente,
        private readonly Reloj $reloj,
    ) {}

    /** @throws ReglaDeNegocioViolada `rango_de_fechas_invalido` | PeriodoDuplicado */
    public function ejecutar(string $nombre, DateTimeImmutable $fechaInicio, DateTimeImmutable $fechaFin): PeriodoConVigencia
    {
        $periodo = new PeriodoEscolar(Identificador::generar(), $nombre, $fechaInicio, $fechaFin, Instante::truncadoASegundos($this->reloj->ahora()));

        if ($this->periodos->existeNombre($nombre)) {
            throw new PeriodoDuplicado;
        }

        $this->periodos->guardarNuevo($periodo);

        return new PeriodoConVigencia($periodo, $this->periodoVigente->identificador()?->igualA($periodo->id()) === true);
    }
}
