<?php

namespace Src\Identidad\Aplicacion;

use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Dominio\Usuario;

/** Consulta de la sesión vigente (PT-10): el usuario de la sesión, o nada si no hay sesión o el usuario tiene borrado lógico. */
final class VerSesion
{
    public function __construct(
        private readonly UsuarioActual $actual,
        private readonly RepositorioDeUsuarios $usuarios,
    ) {}

    public function ejecutar(): ?Usuario
    {
        $id = $this->actual->identificador();

        return $id === null ? null : $this->usuarios->buscarPorId($id);
    }
}
