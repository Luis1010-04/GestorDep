<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\CalificacionLeida;

/**
 * @property CalificacionLeida $resource
 */
class CalificacionRecurso extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: string,
     *     inscripcion_id: string,
     *     tipo: string,
     *     numero_parcial: int|null,
     *     valor: string|null,
     *     criterio: array{ambito: string, calificacion_minima: string|null},
     *     fecha: string,
     *     actualizado_en: string
     * }
     */
    public function toArray(Request $request): array
    {
        $calificacion = $this->resource;

        return [
            'id' => $calificacion->id->valor(),
            'inscripcion_id' => $calificacion->inscripcionId->valor(),
            'tipo' => $calificacion->tipo->value,
            'numero_parcial' => $calificacion->numeroParcial,
            'valor' => DecimalComoCadena::de($calificacion->valor),
            'criterio' => [
                'ambito' => $calificacion->criterio->aplicaA()->value,
                'calificacion_minima' => DecimalComoCadena::de($calificacion->criterio->calificacionMinima()),
            ],
            'fecha' => $calificacion->fecha->format('Y-m-d'),
            'actualizado_en' => $calificacion->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
