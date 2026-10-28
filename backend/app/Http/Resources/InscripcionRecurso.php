<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\InscripcionConJugador;

/**
 * @property InscripcionConJugador $resource
 */
class InscripcionRecurso extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: string,
     *     materia_id: string,
     *     jugador: array{id: string, nombres: string, apellidos: string, matricula: string},
     *     actualizado_en: string
     * }
     */
    public function toArray(Request $request): array
    {
        $inscripcion = $this->resource;

        return [
            'id' => $inscripcion->id->valor(),
            'materia_id' => $inscripcion->materiaId->valor(),
            'jugador' => [
                'id' => $inscripcion->jugadorId->valor(),
                'nombres' => $inscripcion->nombres,
                'apellidos' => $inscripcion->apellidos,
                'matricula' => $inscripcion->matricula,
            ],
            'actualizado_en' => $inscripcion->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
