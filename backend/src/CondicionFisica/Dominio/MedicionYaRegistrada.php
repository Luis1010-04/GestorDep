<?php

namespace Src\CondicionFisica\Dominio;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** El entrenador ya registró una medición del jugador en el periodo, y no pidió corregirla (docs/convenciones-api.md §4.1). */
final class MedicionYaRegistrada extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('medicion_ya_registrada');
    }
}
