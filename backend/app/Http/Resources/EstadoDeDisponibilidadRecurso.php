<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Salud\Aplicacion\EstadoVisible;

/**
 * El estado oficial de disponibilidad (§6.2), sin tipo, zona ni causa de la lesión (RN-15) y sin `confirmado_por` (DA-18).
 *
 * @property EstadoVisible $resource
 */
class EstadoDeDisponibilidadRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'estado' => $this->resource->estado,
            'fecha_estimada_regreso' => $this->resource->fechaEstimadaRegreso,
            'vigente_desde' => $this->resource->vigenteDesde?->format(DateTimeInterface::ATOM),
            'es_implicito' => $this->resource->esImplicito,
            'requiere_actualizacion' => $this->resource->requiereActualizacion,
            'lesion_id' => $this->resource->lesionId,
        ];
    }
}
