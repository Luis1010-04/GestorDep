<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\AsistenciaLeida;

/**
 * @property AsistenciaLeida $resource
 */
class AsistenciaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, inscripcion_id: string, fecha: string, estado: string, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        $asistencia = $this->resource;

        return [
            'id' => $asistencia->id->valor(),
            'inscripcion_id' => $asistencia->inscripcionId->valor(),
            'fecha' => $asistencia->fecha->format('Y-m-d'),
            'estado' => $asistencia->estado->value,
            'actualizado_en' => $asistencia->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
