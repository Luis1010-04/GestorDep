<?php

namespace Src\Identidad\Infraestructura\Modelos;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

/**
 * Modelo de autenticación sobre `usuarios`; no es una entidad de dominio. Los usuarios con borrado
 * lógico no se recuperan, y su contraseña es `contrasena_hash`.
 *
 * @property string $id
 */
class UsuarioModelo extends Authenticatable
{
    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'usuarios';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function booted(): void
    {
        static::addGlobalScope('no_eliminados', fn (Builder $consulta) => $consulta->whereNull('eliminado_en'));
    }

    public function getAuthPassword(): string
    {
        return (string) $this->getAttribute('contrasena_hash');
    }

    /** @return list<string> */
    public function codigosDeRoles(): array
    {
        /** @var list<string> $codigos */
        $codigos = DB::table('usuario_rol')
            ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->where('usuario_rol.usuario_id', $this->getKey())
            ->orderBy('roles.codigo')
            ->pluck('roles.codigo')
            ->all();

        return $codigos;
    }
}
