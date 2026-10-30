<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Rendimiento\Aplicacion\UmbralAplicable;

/**
 * El umbral que se aplica a un jugador (HU-15, v1.0.4 §6.2): el propio (`origen` umbral) o la plantilla de fábrica de
 * su posición (`origen` plantilla, sin `vigente_desde`).
 *
 * @property UmbralAplicable $resource
 */
class UmbralRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'origen' => $this->resource->origen,
            'plantilla_id' => $this->resource->plantillaId?->valor(),
            'calificacion_minima' => DecimalComoCadena::de($this->resource->criterio->calificacionMinima()),
            'ventana_partidos' => $this->resource->criterio->ventanaPartidos(),
            'vigente_desde' => $this->resource->vigenteDesde?->format(DateTimeInterface::ATOM),
        ];
    }
}
