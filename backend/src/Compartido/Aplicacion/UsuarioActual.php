<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Usuario autenticado que ejecuta el caso de uso. La implementacion llega con la autenticacion (H1). */
interface UsuarioActual
{
    public function identificador(): Identificador;
}
