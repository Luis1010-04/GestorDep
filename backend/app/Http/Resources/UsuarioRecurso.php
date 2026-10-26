<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Identidad\Dominio\Usuario;

/**
 * @property Usuario $resource
 */
class UsuarioRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, correo: string, roles: list<string>} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'nombre' => $this->resource->nombre(),
            'correo' => $this->resource->correo(),
            'roles' => $this->resource->roles(),
        ];
    }
}
