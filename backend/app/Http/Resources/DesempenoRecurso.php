<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Rendimiento\Dominio\EvaluacionDeRendimiento;

/**
 * El desempeño de un jugador (HU-16, §6.2): `promedio` es una cadena con dos decimales, nula con datos insuficientes;
 * `calculado_en` nunca es nulo (v1.0.5). Sin identificadores de umbral ni de plantilla (DA-19).
 *
 * @property EvaluacionDeRendimiento $resource
 */
class DesempenoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $calculada = $this->resource->calculada();

        return [
            'resultado' => $calculada->resultado()->value,
            'promedio' => DecimalComoCadena::de($calculada->promedio()),
            'calificacion_minima' => DecimalComoCadena::de($this->resource->criterio()->calificacionMinima()),
            'ventana_partidos' => $this->resource->criterio()->ventanaPartidos(),
            'partidos_considerados' => $calculada->partidosConsiderados(),
            'calculado_en' => $this->resource->calculadaEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
