<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/**
 * El nivel de riesgo académico de un jugador en el periodo vigente, para Alertas (P-09, DA-115). El adaptador vive en
 * Académico y calcula con las reglas de Académico, sin leer `resumenes_academicos`: así el escucha de la alerta no depende
 * del orden respecto al del resumen.
 */
interface RiesgoAcademicoDelJugador
{
    /** @return RiesgoAcademico|null nulo si no hay periodo vigente, o el jugador no tiene datos para calcular un nivel */
    public function de(Identificador $jugador): ?RiesgoAcademico;
}
