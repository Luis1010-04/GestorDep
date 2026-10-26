<?php

namespace Src\Identidad\Aplicacion;

use Src\Compartido\Dominio\ReglaDeNegocioViolada;

/** Usuario inexistente, desactivado o con contraseña incorrecta; nunca distingue el motivo. */
final class CredencialesInvalidas extends ReglaDeNegocioViolada
{
    public function __construct()
    {
        parent::__construct('credenciales_invalidas');
    }
}
