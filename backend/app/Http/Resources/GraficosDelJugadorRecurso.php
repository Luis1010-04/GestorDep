<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Consultas\Aplicacion\GraficosDelJugador;
use Src\Consultas\Aplicacion\SerieDeMateria;

/**
 * Los gráficos de un jugador (HU-25, DA-105). Un campo que el usuario no puede ver se omite: el entrenador recibe las cuatro
 * series de entrenador; el docente, las dos listas de materia.
 *
 * @property GraficosDelJugador $resource
 */
class GraficosDelJugadorRecurso extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $graficos = $this->resource;
        $datos = [];

        if ($graficos->rendimientoPartidos !== null && $graficos->asistenciaEntrenamientos !== null && $graficos->mediciones !== null && $graficos->resumenAcademico !== null) {
            $datos['rendimiento_partidos'] = SerieDeGraficoRecurso::de($graficos->rendimientoPartidos);
            $datos['asistencia_entrenamientos'] = SerieDeGraficoRecurso::de($graficos->asistenciaEntrenamientos);
            $datos['mediciones'] = [
                'peso_kg' => SerieDeGraficoRecurso::de($graficos->mediciones['peso_kg']),
                'estatura_cm' => SerieDeGraficoRecurso::de($graficos->mediciones['estatura_cm']),
                'imc' => SerieDeGraficoRecurso::de($graficos->mediciones['imc']),
            ];
            $datos['resumen_academico'] = SerieDeGraficoRecurso::de($graficos->resumenAcademico);
        }

        if ($graficos->calificaciones !== null && $graficos->asistenciaClases !== null) {
            $datos['calificaciones'] = array_map($this->materia(...), $graficos->calificaciones);
            $datos['asistencia_clases'] = array_map($this->materia(...), $graficos->asistenciaClases);
        }

        return $datos;
    }

    /** @return array<string, mixed> */
    private function materia(SerieDeMateria $materia): array
    {
        return [
            'materia_id' => $materia->materia->id->valor(),
            'asignatura' => ['clave' => $materia->materia->clave, 'nombre' => $materia->materia->nombre],
            'serie' => SerieDeGraficoRecurso::de($materia->serie),
        ];
    }
}
