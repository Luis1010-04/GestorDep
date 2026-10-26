<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Plantel\Aplicacion\Baja;

/**
 * @property Baja $resource
 */
class BajaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'jugador_id' => $this->resource->jugadorId->valor(),
            'motivo' => $this->resource->motivo,
            'fecha' => $this->resource->fecha->format('Y-m-d'),
            'creado_en' => $this->resource->creadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
