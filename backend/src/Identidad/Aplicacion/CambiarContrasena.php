<?php

namespace Src\Identidad\Aplicacion;

use Illuminate\Contracts\Hashing\Hasher;
use RuntimeException;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Identidad\Dominio\PoliticaDeContrasena;
use Src\Identidad\Dominio\RepositorioDeUsuarios;

/** Cambio de contraseña de HU-27: conserva la sesión actual y cierra las demás. */
final class CambiarContrasena
{
    public function __construct(
        private readonly RepositorioDeUsuarios $usuarios,
        private readonly PoliticaDeContrasena $politica,
        private readonly Hasher $hasher,
        private readonly CierreDeSesiones $sesiones,
    ) {}

    /**
     * @throws ReglaDeNegocioViolada contrasena_actual_incorrecta (sin más detalle)
     * @throws ContrasenaNuevaMuyCorta la contraseña nueva no cumple la política
     */
    public function ejecutar(Identificador $usuarioId, string $contrasenaActual, string $contrasenaNueva, string $sesionActual): void
    {
        $usuario = $this->usuarios->buscarPorId($usuarioId) ?? throw new RuntimeException('usuario_de_sesion_inexistente');

        if (! $this->hasher->check($contrasenaActual, $usuario->contrasenaHash())) {
            throw new ReglaDeNegocioViolada('contrasena_actual_incorrecta');
        }

        if (! $this->politica->cumple($contrasenaNueva)) {
            throw new ContrasenaNuevaMuyCorta;
        }

        $usuario->cambiarContrasena($this->hasher->make($contrasenaNueva));
        $this->usuarios->guardarContrasena($usuario);
        $this->sesiones->cerrarOtras($usuarioId, $sesionActual);
    }
}
