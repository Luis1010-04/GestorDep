<?php

namespace App\Http\Resources;

use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Compartido\Aplicacion\JugadorDelEquipo;
use Src\Competencia\Aplicacion\EntrenamientoConJugadores;
use Src\Competencia\Dominio\Entrenamiento;

/**
 * Un entrenamiento, con los jugadores del equipo cuando se da con ellos (alta, detalle y cambio; no en el listado).
 * Los instantes van en UTC (RNF-04).
 *
 * @property Entrenamiento|EntrenamientoConJugadores $resource
 */
class EntrenamientoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $conJugadores = $this->resource instanceof EntrenamientoConJugadores;
        $entrenamiento = $conJugadores ? $this->resource->entrenamiento : $this->resource;
        $utc = new DateTimeZone('UTC');
        $datos = [
            'id' => $entrenamiento->id()->valor(),
            'equipo_id' => $entrenamiento->equipoId()->valor(),
            'lugar_id' => $entrenamiento->lugarId()->valor(),
            'inicia_en' => $entrenamiento->iniciaEn()->setTimezone($utc)->format(DateTimeInterface::ATOM),
            'termina_en' => $entrenamiento->terminaEn()->setTimezone($utc)->format(DateTimeInterface::ATOM),
            'responsable_id' => $entrenamiento->responsableId()->valor(),
            'estado' => $entrenamiento->estado()->value,
            'actualizado_en' => $entrenamiento->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
        if ($conJugadores) {
            $datos['jugadores_del_equipo'] = array_map(fn (JugadorDelEquipo $jugador) => [
                'id' => $jugador->id->valor(),
                'nombres' => $jugador->nombres,
                'apellidos' => $jugador->apellidos,
            ], $this->resource->jugadores);
        }

        return $datos;
    }
}
