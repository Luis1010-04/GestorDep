<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\Torneo;

/**
 * @property Torneo $resource
 */
class TorneoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, temporada_id: string, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'nombre' => $this->resource->nombre(),
            'temporada_id' => $this->resource->temporadaId()->valor(),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
