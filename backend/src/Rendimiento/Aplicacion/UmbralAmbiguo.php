<?php

namespace Src\Rendimiento\Aplicacion;

use Src\Rendimiento\Dominio\ReglaDeCampoViolada;

/** El umbral recibe plantilla_id y valores propios a la vez (v1.0.4 §6.3, 422). */
final class UmbralAmbiguo extends ReglaDeCampoViolada
{
    public function __construct()
    {
        parent::__construct('umbral_ambiguo', 'plantilla_id');
    }
}
