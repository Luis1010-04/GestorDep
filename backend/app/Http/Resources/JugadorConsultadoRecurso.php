<?php

namespace App\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Plantel\Aplicacion\EquipoDelJugador;
use Src\Plantel\Aplicacion\JugadorConEquipos;

/**
 * Mismo esquema `Jugador` que el 201 de POST /jugadores (JugadorRecurso).
 *
 * @property JugadorConEquipos $resource
 */
class JugadorConsultadoRecurso extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $jugador = $this->resource;

        return [
            'id' => $jugador->id,
            'nombres' => $jugador->nombres,
            'apellidos' => $jugador->apellidos,
            'fecha_nacimiento' => $jugador->fechaNacimiento,
            'matricula' => $jugador->matricula,
            'estatus_academico' => $jugador->estatusAcademico,
            'estatus_deportivo' => $jugador->estatusDeportivo,
            'equipos' => array_map(fn (EquipoDelJugador $equipo) => [
                'equipo_id' => $equipo->equipoId,
                'nombre' => $equipo->nombre,
                'categoria' => ['id' => $equipo->categoriaId, 'nombre' => $equipo->categoriaNombre],
                'posicion' => $equipo->posicion,
            ], $jugador->equipos),
            'actualizado_en' => $jugador->actualizadoEn->format(DateTimeInterface::ATOM),
        ];
    }
}
