<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Salud\Dominio\Lesion;

/**
 * El detalle de una lesión (§6.2), solo para quien la registró (RN-15). `fecha_estimada_regreso` es la de registro (DA-02).
 *
 * @property Lesion $resource
 */
class LesionRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'tipo' => $this->resource->tipo(),
            'zona' => $this->resource->zona(),
            'causa' => $this->resource->causa()->value,
            'fecha_inicio' => $this->resource->fechaInicio(),
            'fecha_estimada_regreso' => $this->resource->fechaEstimadaRegreso(),
            'fecha_alta' => $this->resource->fechaAlta(),
            'nota' => $this->resource->nota(),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
