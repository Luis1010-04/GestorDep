<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Plantel\Aplicacion\JugadorBuscado;

/**
 * @property JugadorBuscado $resource
 */
class JugadorBuscadoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{nombres: string, apellidos: string, matricula: string} */
    public function toArray(Request $request): array
    {
        return [
            'nombres' => $this->resource->nombres,
            'apellidos' => $this->resource->apellidos,
            'matricula' => $this->resource->matricula,
        ];
    }
}
