<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\Lugar;
use Src\Competencia\Dominio\Rival;

/**
 * Lugar o rival: los dos catálogos tienen la misma forma (HU-28).
 *
 * @property Lugar|Rival $resource
 */
class LugarRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'nombre' => $this->resource->nombre(),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
