<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\CondicionFisica\Dominio\PruebaFisica;

/**
 * Una prueba física (§6.2), solo para quien la registró (DD-59).
 *
 * @property PruebaFisica $resource
 */
class PruebaFisicaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'equipo_id' => $this->resource->equipoId()->valor(),
            'tipo_prueba_id' => $this->resource->tipoPruebaId()->valor(),
            'fecha' => $this->resource->fecha(),
            'periodo' => $this->resource->periodo(),
            'resultado' => $this->resource->resultado()->comoCadena(2),
            'actualizado_en' => $this->resource->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
