<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Dominio\Asignatura;

/**
 * @property Asignatura $resource
 */
class AsignaturaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, clave: string, nombre: string, grado: int, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'clave' => $this->resource->clave(),
            'nombre' => $this->resource->nombre(),
            'grado' => $this->resource->grado(),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
