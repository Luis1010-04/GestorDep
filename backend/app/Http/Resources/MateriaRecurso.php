<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\MateriaConCriterio;

/**
 * @property MateriaConCriterio $resource
 */
class MateriaRecurso extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     id: string,
     *     asignatura: array{id: string, clave: string, nombre: string},
     *     periodo: array{id: string, nombre: string},
     *     criterio_aplicado: array{ambito: string, calificacion_minima: string|null},
     *     actualizado_en: string
     * }
     */
    public function toArray(Request $request): array
    {
        $materia = $this->resource->materia;
        $criterio = $this->resource->criterio;

        return [
            'id' => $materia->id->valor(),
            'asignatura' => [
                'id' => $materia->asignaturaId->valor(),
                'clave' => $materia->asignaturaClave,
                'nombre' => $materia->asignaturaNombre,
            ],
            'periodo' => ['id' => $materia->periodoId->valor(), 'nombre' => $materia->periodoNombre],
            'criterio_aplicado' => [
                'ambito' => $criterio->aplicaA()->value,
                'calificacion_minima' => DecimalComoCadena::de($criterio->calificacionMinima()),
            ],
            'actualizado_en' => $materia->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
