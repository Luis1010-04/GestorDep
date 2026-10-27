<?php

namespace Src\Academico\Aplicacion;

use Src\Academico\Dominio\RepositorioDeParametros;
use Src\Academico\Dominio\RepositorioDeResumenes;
use Src\Academico\Dominio\ResumenAcademico;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;

/**
 * Recalcula y guarda el resumen académico de un jugador para el periodo vigente (DD-45, arquitectura §11). Es
 * idempotente: con los mismos datos y el mismo reloj deja la misma fila, y repetirlo no duplica nada.
 */
final class RecalcularResumenDeJugador
{
    public function __construct(
        private readonly PeriodoVigente $periodoVigente,
        private readonly FuenteDeDatosAcademicos $datos,
        private readonly RepositorioDeParametros $parametros,
        private readonly RepositorioDeResumenes $resumenes,
        private readonly Reloj $reloj,
    ) {}

    /** @return bool falso si hoy no cae en ningún periodo: no se calcula ni se escribe nada */
    public function ejecutar(Identificador $jugador): bool
    {
        $periodo = $this->periodoVigente->identificador();
        if ($periodo === null) {
            return false;
        }

        $resumen = ResumenAcademico::calcular(
            $this->datos->materiasEvaluadas($jugador, $periodo),
            $this->datos->conteoDeAsistencia($jugador, $periodo),
            $this->parametros->riesgoAcademico(),
        );
        $this->resumenes->guardar($jugador, $periodo, $resumen, Instante::truncadoASegundos($this->reloj->ahora()));

        return true;
    }
}
