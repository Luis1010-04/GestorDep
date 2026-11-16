<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Consultas\Aplicacion\AlertaVisible;

/**
 * Una alerta abierta (HU-22, DA-105). No incluye `cerrada_en`, porque solo se devuelven las abiertas. `causa` es un objeto
 * con el dato que la activa, nunca un texto libre.
 *
 * @property AlertaVisible $resource
 */
class AlertaRecurso extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $alerta = $this->resource;

        return [
            'id' => $alerta->id->valor(),
            'tipo' => $alerta->tipo,
            'jugador_id' => $alerta->jugadorId->valor(),
            'equipo_id' => $alerta->equipoId?->valor(),
            'causa' => (object) $alerta->causa,
            'activada_en' => $alerta->activadaEn->format(DateTimeInterface::ATOM),
        ];
    }
}
