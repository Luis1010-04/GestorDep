<?php

namespace Src\Identidad\Infraestructura;

use Illuminate\Contracts\Auth\Factory;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Infraestructura\Modelos\UsuarioModelo;

/** Usuario de la sesión web; sin sesión (consola, ruta pública) no hay identificador ni roles. */
final class UsuarioActualDeSesion implements UsuarioActual
{
    public function __construct(private readonly Factory $autenticacion) {}

    public function identificador(): ?Identificador
    {
        $id = $this->autenticacion->guard()->id();

        return is_string($id) ? Identificador::desdeTexto($id) : null;
    }

    /** @return list<string> */
    public function roles(): array
    {
        $usuario = $this->autenticacion->guard()->user();

        return $usuario instanceof UsuarioModelo ? $usuario->codigosDeRoles() : [];
    }
}
