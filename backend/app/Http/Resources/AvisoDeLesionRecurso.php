<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Salud\Aplicacion\AvisoVisible;

/**
 * Lo que el usuario ve de un aviso (§6.2): sin `creado_por`, `confirmado_por`, `descartado_por` ni `lesion_id` (DA-18).
 *
 * @property AvisoVisible $resource
 */
class AvisoDeLesionRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'estado' => $this->resource->estado,
            'fecha_aviso' => $this->resource->fechaAviso,
            'reportado_por_jugador' => $this->resource->reportadoPorJugador,
            'nota' => $this->resource->nota,
            'nota_descarte' => $this->resource->notaDescarte,
            'es_propio' => $this->resource->esPropio,
            'creado_en' => $this->resource->creadoEn->format(DateTimeInterface::ATOM),
            'actualizado_en' => $this->resource->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
