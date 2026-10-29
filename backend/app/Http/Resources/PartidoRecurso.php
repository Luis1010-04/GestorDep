<?php

namespace App\Http\Resources;

use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Competencia\Dominio\Partido;

/**
 * Un partido. No lleva la categoría: se toma del equipo (DD-18). Los instantes van en UTC (RNF-04).
 *
 * @property Partido $resource
 */
class PartidoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, string|int|null> */
    public function toArray(Request $request): array
    {
        $partido = $this->resource;

        return [
            'id' => $partido->id()->valor(),
            'equipo_id' => $partido->equipoId()->valor(),
            'temporada_id' => $partido->temporadaId()->valor(),
            'torneo_id' => $partido->torneoId()?->valor(),
            'rival_id' => $partido->rivalId()->valor(),
            'lugar_id' => $partido->lugarId()->valor(),
            'inicia_en' => $partido->iniciaEn()->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM),
            'tipo' => $partido->tipo()->value,
            'condicion' => $partido->condicion()->value,
            'estado' => $partido->estado()->value,
            'goles_favor' => $partido->golesFavor(),
            'goles_contra' => $partido->golesContra(),
            'actualizado_en' => $partido->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
