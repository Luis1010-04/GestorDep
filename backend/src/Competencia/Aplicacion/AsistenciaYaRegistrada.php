<?php

namespace Src\Competencia\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La asistencia se captura una sola vez por entrenamiento y jugador; la corrección es `PATCH` (§3.4). */
final class AsistenciaYaRegistrada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('asistencia_ya_registrada');
    }
}
