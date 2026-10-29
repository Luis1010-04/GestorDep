<?php

namespace Src\Academico\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Un valor numérico válido pero fuera de la escala de 0 a 10 (DD-51); `campo()` es el campo de la petición que lo causó. */
final class CalificacionFueraDeRango extends ReglaDeNegocioViolada
{
    public function __construct(private readonly string $campo)
    {
        parent::__construct('calificacion_fuera_de_rango');
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
