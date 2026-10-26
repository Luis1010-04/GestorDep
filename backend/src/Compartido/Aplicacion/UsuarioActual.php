<?php

namespace Src\Compartido\Aplicacion;

use Src\Compartido\Dominio\Identificador;

/** Usuario autenticado que ejecuta el caso de uso; nulo cuando no hay sesion (por ejemplo, un comando de consola). */
interface UsuarioActual
{
    public function identificador(): ?Identificador;

    /** @return list<string> codigos de los roles del usuario; vacio sin sesion */
    public function roles(): array;
}
