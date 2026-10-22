<?php

namespace Src\Identidad\Aplicacion;

use Illuminate\Contracts\Hashing\Hasher;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Identidad\Dominio\PoliticaDeContrasena;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Dominio\Usuario;

/** Alta de un usuario (RN-19). La contraseña se valida y se guarda solo como hash. */
final class CrearUsuario
{
    public function __construct(
        private readonly RepositorioDeUsuarios $usuarios,
        private readonly PoliticaDeContrasena $politica,
        private readonly Hasher $hasher,
    ) {}

    /**
     * @param  list<string>  $roles  códigos de rol
     *
     * @throws ReglaDeNegocioViolada contrasena_muy_corta, rol_obligatorio, rol_inexistente o correo_duplicado
     */
    public function ejecutar(string $correo, string $nombre, array $roles, string $contrasena): Usuario
    {
        if (! $this->politica->cumple($contrasena)) {
            throw new ReglaDeNegocioViolada('contrasena_muy_corta');
        }

        $roles = array_values(array_unique($roles));
        if ($roles === []) {
            throw new ReglaDeNegocioViolada('rol_obligatorio');
        }

        foreach ($roles as $codigo) {
            if (! $this->usuarios->existeRol($codigo)) {
                throw new ReglaDeNegocioViolada('rol_inexistente');
            }
        }

        if ($this->usuarios->existeCorreo($correo)) {
            throw new ReglaDeNegocioViolada('correo_duplicado');
        }

        $usuario = Usuario::crear(Identificador::generar(), $nombre, $correo, $this->hasher->make($contrasena), $roles);
        $this->usuarios->guardarNuevo($usuario);

        return $usuario;
    }
}
