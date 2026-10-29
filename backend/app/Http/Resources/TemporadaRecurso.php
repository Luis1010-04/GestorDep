<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\Temporada;

/**
 * @property Temporada $resource
 */
class TemporadaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, fecha_inicio: string, fecha_fin: string, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'nombre' => $this->resource->nombre(),
            'fecha_inicio' => $this->resource->fechaInicio()->format('Y-m-d'),
            'fecha_fin' => $this->resource->fechaFin()->format('Y-m-d'),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
