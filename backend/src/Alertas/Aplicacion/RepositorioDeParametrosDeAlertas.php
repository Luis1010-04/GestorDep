<?php

namespace Src\Alertas\Aplicacion;

use Src\Alertas\Dominio\ParametrosDeSanciones;

/** Los parámetros de `parametros_institucionales` que usan las alertas (DA-100, RNF-09). */
interface RepositorioDeParametrosDeAlertas
{
    public function sanciones(): ParametrosDeSanciones;

    /** El nivel de riesgo académico que activa la alerta académica (RN-21). */
    public function nivelQueActivaLaAlertaAcademica(): string;
}
