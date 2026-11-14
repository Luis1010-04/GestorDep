<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\CondicionFisica\Dominio\TipoDePrueba;

/**
 * Un tipo de prueba del catálogo (§6.2), sin su rango.
 *
 * @property TipoDePrueba $resource
 */
class TipoDePruebaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'codigo' => $this->resource->codigo(),
            'nombre' => $this->resource->nombre(),
            'unidad' => $this->resource->unidad(),
            'mejor_resultado' => $this->resource->mejorResultado(),
        ];
    }
}
