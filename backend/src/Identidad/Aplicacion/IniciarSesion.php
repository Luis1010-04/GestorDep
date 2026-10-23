<?php

namespace Src\Identidad\Aplicacion;

use Illuminate\Contracts\Hashing\Hasher;
use Src\Compartido\Dominio\Reloj;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Dominio\Usuario;

/** Verifica las credenciales de HU-01 y aplica el bloqueo por intentos de RNF-10 (DD-29). */
final class IniciarSesion
{
    public function __construct(
        private readonly RepositorioDeUsuarios $usuarios,
        private readonly Hasher $hasher,
        private readonly Reloj $reloj,
        private readonly int $intentosMaximos,
        private readonly int $minutosDeBloqueo,
    ) {}

    /**
     * @throws CredencialesInvalidas usuario inexistente, desactivado o contraseña incorrecta
     * @throws CuentaBloqueada la cuenta está bloqueada; no se revisa la contraseña ni se cuenta el intento
     */
    public function ejecutar(string $correo, string $contrasena): Usuario
    {
        $usuario = $this->usuarios->buscarPorCorreo($correo);
        if ($usuario === null || ! $usuario->activo()) {
            throw new CredencialesInvalidas;
        }

        $ahora = $this->reloj->ahora();
        $bloqueadoHasta = $usuario->bloqueadoHasta();
        if ($bloqueadoHasta !== null && $usuario->estaBloqueado($ahora)) {
            throw new CuentaBloqueada($bloqueadoHasta->getTimestamp() - $ahora->getTimestamp());
        }

        if (! $this->hasher->check($contrasena, $usuario->contrasenaHash())) {
            $usuario->registrarIntentoFallido($ahora, $this->intentosMaximos, $this->minutosDeBloqueo);
            $this->usuarios->guardarAcceso($usuario);

            throw new CredencialesInvalidas;
        }

        $usuario->registrarAcceso();
        $this->usuarios->guardarAcceso($usuario);

        return $usuario;
    }
}
