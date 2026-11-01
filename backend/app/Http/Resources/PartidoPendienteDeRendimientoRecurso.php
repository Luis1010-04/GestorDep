<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Consultas\Aplicacion\PartidoPendienteDeRendimiento;

/**
 * Un partido jugado con convocados sin rendimiento (HU-14), sin datos de los jugadores.
 *
 * @property PartidoPendienteDeRendimiento $resource
 */
class PartidoPendienteDeRendimientoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{partido_id: string, equipo_id: string, inicia_en: string, convocados_sin_rendimiento: int} */
    public function toArray(Request $request): array
    {
        return [
            'partido_id' => $this->resource->partidoId->valor(),
            'equipo_id' => $this->resource->equipoId->valor(),
            'inicia_en' => $this->resource->iniciaEn->format(DateTimeInterface::ATOM),
            'convocados_sin_rendimiento' => $this->resource->convocadosSinRendimiento,
        ];
    }
}
