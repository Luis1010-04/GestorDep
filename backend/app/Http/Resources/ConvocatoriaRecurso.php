<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\Convocatoria;

/**
 * Un convocado, sin datos del jugador (DA-29). La posición y el rol son nulos hasta la alineación (T07).
 *
 * @property Convocatoria $resource
 */
class ConvocatoriaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, partido_id: string, jugador_id: string, posicion_id: string|null, rol_alineacion: string|null, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'partido_id' => $this->resource->partidoId()->valor(),
            'jugador_id' => $this->resource->jugadorId()->valor(),
            'posicion_id' => $this->resource->posicionId()?->valor(),
            'rol_alineacion' => $this->resource->rolAlineacion()?->value,
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
