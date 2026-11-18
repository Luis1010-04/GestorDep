<?php

namespace Src\Academico\Infraestructura;

use Src\Academico\Aplicacion\FuenteDeDatosAcademicos;
use Src\Academico\Aplicacion\PeriodoVigente;
use Src\Academico\Dominio\RepositorioDeParametros;
use Src\Academico\Dominio\ResumenAcademico;
use Src\Compartido\Aplicacion\RiesgoAcademico;
use Src\Compartido\Aplicacion\RiesgoAcademicoDelJugador;
use Src\Compartido\Dominio\Identificador;

/**
 * Adaptador del puerto de Compartido para Alertas (P-09, DA-115): calcula el nivel de riesgo, el promedio y las materias
 * reprobadas del periodo vigente con la misma regla que el resumen (`ResumenAcademico`), pero sin leer ni escribir
 * `resumenes_academicos`, para que el escucha de la alerta no dependa del orden respecto al del resumen.
 */
final class RiesgoAcademicoDelJugadorAcademico implements RiesgoAcademicoDelJugador
{
    public function __construct(
        private readonly PeriodoVigente $periodoVigente,
        private readonly FuenteDeDatosAcademicos $datos,
        private readonly RepositorioDeParametros $parametros,
    ) {}

    public function de(Identificador $jugador): ?RiesgoAcademico
    {
        $periodo = $this->periodoVigente->identificador();
        if ($periodo === null) {
            return null;
        }

        $resumen = ResumenAcademico::calcular(
            $this->datos->materiasEvaluadas($jugador, $periodo),
            $this->datos->conteoDeAsistencia($jugador, $periodo),
            $this->parametros->riesgoAcademico(),
        );
        $nivel = $resumen->nivelRiesgo();

        return $nivel === null ? null : new RiesgoAcademico($nivel->value, $resumen->promedio()?->comoCadena(), $resumen->materiasReprobadas());
    }
}
