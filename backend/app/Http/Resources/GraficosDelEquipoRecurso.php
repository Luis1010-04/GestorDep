<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Consultas\Aplicacion\GraficosDelEquipo;

/**
 * Los gráficos de un equipo (HU-25 c2, DA-105): `equipo` siempre y `jugador` solo con `filtro[jugador_id]`.
 *
 * @property GraficosDelEquipo $resource
 */
class GraficosDelEquipoRecurso extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $graficos = $this->resource;
        $datos = [
            'equipo' => [
                'rendimiento_partidos' => SerieDeGraficoRecurso::de($graficos->rendimientoPartidos),
                'asistencia_entrenamientos' => SerieDeGraficoRecurso::de($graficos->asistenciaEntrenamientos),
            ],
        ];

        if ($graficos->jugadorId !== null && $graficos->rendimientoDelJugador !== null && $graficos->asistenciaDelJugador !== null) {
            $datos['jugador'] = [
                'jugador_id' => $graficos->jugadorId->valor(),
                'rendimiento_partidos' => SerieDeGraficoRecurso::de($graficos->rendimientoDelJugador),
                'asistencia_entrenamientos' => SerieDeGraficoRecurso::de($graficos->asistenciaDelJugador),
            ];
        }

        return $datos;
    }
}
