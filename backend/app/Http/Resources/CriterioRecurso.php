<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Dominio\CriterioVigente;

/**
 * @property CriterioVigente $resource
 */
class CriterioRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{ambito: string, calificacion_minima: string|null, vigente_desde: string} */
    public function toArray(Request $request): array
    {
        return [
            'ambito' => $this->resource->criterio()->aplicaA()->value,
            'calificacion_minima' => DecimalComoCadena::de($this->resource->criterio()->calificacionMinima()),
            'vigente_desde' => $this->resource->vigenteDesde()->format('Y-m-d'),
        ];
    }
}
