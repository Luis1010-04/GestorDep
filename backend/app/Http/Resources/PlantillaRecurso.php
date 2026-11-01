<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Rendimiento\Dominio\PlantillaDeEvaluacion;

/**
 * Una plantilla de evaluación (HU-15, v1.0.4 §6.2). No incluye `creada_por`: el entrenador solo ve las de fábrica y las
 * suyas, y `es_de_fabrica` basta para distinguirlas (DA-19).
 *
 * @property PlantillaDeEvaluacion $resource
 */
class PlantillaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->valor(),
            'nombre' => $this->resource->nombre(),
            'posicion_id' => $this->resource->posicionId()->valor(),
            'calificacion_minima' => DecimalComoCadena::de($this->resource->criterio()->calificacionMinima()),
            'ventana_partidos' => $this->resource->criterio()->ventanaPartidos(),
            'es_de_fabrica' => $this->resource->esDeFabrica(),
        ];
    }
}
