<?php

namespace Src\Identidad\Infraestructura;

use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Dominio\Usuario;

/** Escribe `usuarios` y `usuarios_historial` según DD-33 (sin hash ni contadores) y DD-34 (`usuario_afectado_id`). */
final class RepositorioDeUsuariosEloquent implements RepositorioDeUsuarios
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
    ) {}

    public function existeCorreo(string $correo): bool
    {
        return $this->conexion->table('usuarios')->where('correo', $correo)->whereNull('eliminado_en')->exists();
    }

    public function existeRol(string $codigo): bool
    {
        return $this->conexion->table('roles')->where('codigo', $codigo)->exists();
    }

    public function guardarNuevo(Usuario $usuario): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();

        $this->historial->registrar(
            'usuarios',
            'usuario_afectado_id',
            $usuario->id(),
            'crear',
            null,
            [
                'nombre' => $usuario->nombre(),
                'correo' => $usuario->correo(),
                'activo' => $usuario->activo(),
                'roles' => $usuario->roles(),
            ],
            function () use ($usuario, $autor): void {
                $this->conexion->table('usuarios')->insert([
                    'id' => $usuario->id()->valor(),
                    'nombre' => $usuario->nombre(),
                    'correo' => $usuario->correo(),
                    'contrasena_hash' => $usuario->contrasenaHash(),
                    'activo' => $usuario->activo(),
                    'intentos_fallidos' => $usuario->intentosFallidos(),
                    'creado_por' => $autor,
                    'actualizado_por' => $autor,
                ]);

                foreach ($usuario->roles() as $codigo) {
                    $this->conexion->table('usuario_rol')->insert([
                        'usuario_id' => $usuario->id()->valor(),
                        'rol_id' => $this->conexion->table('roles')->where('codigo', $codigo)->value('id'),
                    ]);
                }
            },
        );
    }
}
