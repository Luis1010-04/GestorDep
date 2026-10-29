<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\AsistenciaDeEntrenamiento;

/**
 * Un registro de asistencia a un entrenamiento, sin datos del jugador (DA-19).
 *
 * @property AsistenciaDeEntrenamiento $resource
 */
class AsistenciaDeEntrenamientoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, entrenamiento_id: string, jugador_id: string, estado: string, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'entrenamiento_id' => $this->resource->entrenamientoId()->valor(),
            'jugador_id' => $this->resource->jugadorId()->valor(),
            'estado' => $this->resource->estado()->value,
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
