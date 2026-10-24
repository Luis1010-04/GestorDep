<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeNuevoJugador;
use App\Http\Resources\JugadorRecurso;
use App\Http\Respuestas\Respuesta;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\RegistrarJugador;

class JugadorController extends Controller
{
    public function crear(SolicitudDeNuevoJugador $solicitud, RegistrarJugador $registrarJugador): JsonResponse
    {
        $jugador = $registrarJugador->ejecutar(
            (string) $solicitud->input('nombres'),
            (string) $solicitud->input('apellidos'),
            new DateTimeImmutable((string) $solicitud->input('fecha_nacimiento')),
            (string) $solicitud->input('matricula'),
            Identificador::desdeTexto((string) $solicitud->input('equipo_id')),
            (string) $solicitud->input('posicion'),
        );

        return Respuesta::conDatos((new JugadorRecurso($jugador))->resolve($solicitud), estado: 201);
    }
}
