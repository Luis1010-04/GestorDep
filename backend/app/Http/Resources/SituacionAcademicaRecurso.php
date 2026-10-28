<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Academico\Aplicacion\MateriaDeLaSituacion;
use Src\Academico\Aplicacion\SituacionAcademica;

/**
 * La clave `materias` solo aparece para quien es docente del jugador, y solo con sus propias materias (RN-13, DD-48): el
 * entrenador recibe el resumen simplificado, sin calificaciones individuales.
 *
 * @property SituacionAcademica $resource
 */
class SituacionAcademicaRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $situacion = $this->resource;
        $resumen = $situacion->resumen;

        $datos = [
            'periodo' => $situacion->periodoId === null ? null : ['id' => $situacion->periodoId->valor(), 'nombre' => $situacion->periodoNombre],
            'promedio' => DecimalComoCadena::de($resumen?->promedio),
            'materias_reprobadas' => $resumen === null ? 0 : $resumen->materiasReprobadas,
            'materias_sin_calificar' => $resumen === null ? 0 : $resumen->materiasSinCalificar,
            'dato_incompleto' => $situacion->datoIncompleto(),
            'porcentaje_asistencia' => DecimalComoCadena::de($resumen?->porcentajeAsistencia),
            'nivel_riesgo' => $resumen?->nivelRiesgo?->value,
            'calculado_en' => $resumen?->calculadoEn->format(DateTimeInterface::ATOM),
        ];

        if ($situacion->materias !== null) {
            $datos['materias'] = array_map(fn (MateriaDeLaSituacion $materia): array => [
                'materia_id' => $materia->materiaId->valor(),
                'asignatura' => ['clave' => $materia->clave, 'nombre' => $materia->nombre],
                'calificacion' => DecimalComoCadena::de($materia->calificacion),
                'criterio' => [
                    'ambito' => $materia->criterio->aplicaA()->value,
                    'calificacion_minima' => DecimalComoCadena::de($materia->criterio->calificacionMinima()),
                ],
                'aprobada' => $materia->aprobada,
                'calificaciones' => array_map(fn ($calificacion): array => (new CalificacionRecurso($calificacion))->resolve($request), $materia->calificaciones),
            ], $situacion->materias);
        }

        return $datos;
    }
}
