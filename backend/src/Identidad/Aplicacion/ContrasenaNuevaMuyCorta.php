<?php

namespace Src\Identidad\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** La contraseña nueva no cumple la política de RNF-10; el error se reporta sobre el campo `contrasena_nueva`. */
final class ContrasenaNuevaMuyCorta extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('contrasena_muy_corta');
    }

    public function campo(): string
    {
        return 'contrasena_nueva';
    }
}
