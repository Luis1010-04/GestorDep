<?php

namespace Src\Identidad\Infraestructura;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Src\Compartido\Aplicacion\UsuarioActual;
use Src\Compartido\Dominio\Identificador;
use Src\Compartido\Dominio\Instante;
use Src\Compartido\Dominio\Reloj;
use Src\Compartido\Infraestructura\Historial\EscritorDeHistorial;
use Src\Identidad\Dominio\RepositorioDeUsuarios;
use Src\Identidad\Dominio\Usuario;
use stdClass;

/** Escribe `usuarios` y `usuarios_historial` según DD-33 (sin hash ni contadores) y DD-34 (`usuario_afectado_id`). */
final class RepositorioDeUsuariosEloquent implements RepositorioDeUsuarios
{
    public function __construct(
        private readonly ConnectionInterface $conexion,
        private readonly EscritorDeHistorial $historial,
        private readonly UsuarioActual $usuarioActual,
        private readonly Reloj $reloj,
    ) {}

    public function existeCorreo(string $correo): bool
    {
        return $this->conexion->table('usuarios')->where('correo', $correo)->whereNull('eliminado_en')->exists();
    }

    public function existeRol(string $codigo): bool
    {
        return $this->conexion->table('roles')->where('codigo', $codigo)->exists();
    }

    public function buscarPorCorreo(string $correo): ?Usuario
    {
        return $this->reconstituir(
            $this->conexion->table('usuarios')->where('correo', $correo)->whereNull('eliminado_en')->first(),
        );
    }

    public function buscarPorId(Identificador $id): ?Usuario
    {
        return $this->reconstituir(
            $this->conexion->table('usuarios')->where('id', $id->valor())->whereNull('eliminado_en')->first(),
        );
    }

    private function reconstituir(?stdClass $fila): ?Usuario
    {
        if ($fila === null) {
            return null;
        }

        /** @var list<string> $roles */
        $roles = $this->conexion->table('usuario_rol')
            ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->where('usuario_rol.usuario_id', $fila->id)
            ->orderBy('roles.codigo')
            ->pluck('roles.codigo')
            ->all();

        return Usuario::reconstituir(
            Identificador::desdeTexto($fila->id),
            $fila->nombre,
            $fila->correo,
            $fila->contrasena_hash,
            (bool) $fila->activo,
            $roles,
            (int) $fila->intentos_fallidos,
            $fila->bloqueado_hasta === null
                ? null
                : (new DateTimeImmutable($fila->bloqueado_hasta))->setTimezone(new DateTimeZone('UTC')),
        );
    }

    public function guardarAcceso(Usuario $usuario): void
    {
        $this->conexion->table('usuarios')->where('id', $usuario->id()->valor())->update([
            'intentos_fallidos' => $usuario->intentosFallidos(),
            'bloqueado_hasta' => $usuario->bloqueadoHasta()?->format('Y-m-d H:i:s.uP'),
        ]);
    }

    public function modificarAccesoConCandado(Identificador $id, callable $cambio): void
    {
        $this->conexion->transaction(function () use ($id, $cambio): void {
            $usuario = $this->reconstituir(
                $this->conexion->table('usuarios')->where('id', $id->valor())->whereNull('eliminado_en')->lockForUpdate()->first(),
            );
            if ($usuario === null) {
                return;
            }

            $cambio($usuario);
            $this->guardarAcceso($usuario);
        });
    }

    public function guardarContrasena(Usuario $usuario): void
    {
        $momento = Instante::truncadoASegundos($this->reloj->ahora());

        $this->historial->registrar(
            'usuarios',
            'usuario_afectado_id',
            $usuario->id(),
            'actualizar',
            [],
            ['contrasena_modificada' => true],
            fn () => $this->conexion->table('usuarios')->where('id', $usuario->id()->valor())->update([
                'contrasena_hash' => $usuario->contrasenaHash(),
                'actualizado_en' => $momento->format('Y-m-d H:i:sP'),
                'actualizado_por' => $this->usuarioActual->identificador()?->valor(),
            ]),
            instante: $momento,
        );
    }

    public function guardarNuevo(Usuario $usuario): void
    {
        $autor = $this->usuarioActual->identificador()?->valor();
        $momento = Instante::truncadoASegundos($this->reloj->ahora());
        $instante = $momento->format('Y-m-d H:i:sP');

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
            function () use ($usuario, $autor, $instante): void {
                $this->conexion->table('usuarios')->insert([
                    'id' => $usuario->id()->valor(),
                    'nombre' => $usuario->nombre(),
                    'correo' => $usuario->correo(),
                    'contrasena_hash' => $usuario->contrasenaHash(),
                    'activo' => $usuario->activo(),
                    'intentos_fallidos' => $usuario->intentosFallidos(),
                    'creado_en' => $instante,
                    'actualizado_en' => $instante,
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
            instante: $momento,
        );
    }
}
