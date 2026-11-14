<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\CondicionFisica\Dominio\MedicionCorporal;

/**
 * Una medición (§6.2), solo para quien la registró (DD-59).
 *
 * @property MedicionCorporal $resource
 */
class MedicionRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'equipo_id' => $this->resource->equipoId()->valor(),
            'fecha' => $this->resource->fecha(),
            'periodo' => $this->resource->periodo(),
            'peso_kg' => $this->resource->pesoKg()->comoCadena(2),
            'estatura_cm' => $this->resource->estaturaCm()->comoCadena(1),
            'imc' => $this->resource->imc()->comoCadena(2),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
