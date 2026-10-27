<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\PeriodoConVigencia;

/**
 * @property PeriodoConVigencia $resource
 */
class PeriodoEscolarRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array{id: string, nombre: string, fecha_inicio: string, fecha_fin: string, vigente: bool, actualizado_en: string} */
    public function toArray(Request $request): array
    {
        $periodo = $this->resource->periodo;

        return [
            'id' => $periodo->id()->valor(),
            'nombre' => $periodo->nombre(),
            'fecha_inicio' => $periodo->fechaInicio()->format('Y-m-d'),
            'fecha_fin' => $periodo->fechaFin()->format('Y-m-d'),
            'vigente' => $this->resource->vigente,
            'actualizado_en' => $periodo->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
