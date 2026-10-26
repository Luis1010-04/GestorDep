<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Plantel\Aplicacion\JugadorRegistrado;

/**
 * @property JugadorRegistrado $resource
 */
class JugadorRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $jugador = $this->resource->jugador;
        $equipo = $this->resource->equipo->equipo;

        return [
            'id' => $jugador->id()->valor(),
            'nombres' => $jugador->nombres(),
            'apellidos' => $jugador->apellidos(),
            'fecha_nacimiento' => $jugador->fechaNacimiento()->format('Y-m-d'),
            'matricula' => $jugador->matricula(),
            'estatus_academico' => $jugador->estatusAcademico(),
            'estatus_deportivo' => $jugador->estatusDeportivo(),
            'equipos' => [[
                'equipo_id' => $equipo->id()->valor(),
                'nombre' => $equipo->nombre(),
                'categoria' => ['id' => $equipo->categoriaId()->valor(), 'nombre' => $this->resource->equipo->categoriaNombre],
                'posicion' => $this->resource->posicion,
            ]],
            'actualizado_en' => $jugador->actualizadoEn()->format(DateTimeInterface::ATOM),
        ];
    }
}
