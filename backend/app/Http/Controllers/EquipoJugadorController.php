<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeAsignacionDeJugador;
use App\Http\Requests\SolicitudDeCambioDePosicion;
use App\Http\Resources\JugadorConsultadoRecurso;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\AsignarJugadorAEquipo;
use Src\Plantel\Aplicacion\CambiarPosicionDeJugador;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;

class EquipoJugadorController extends Controller
{
    public function asignar(SolicitudDeAsignacionDeJugador $solicitud, string $equipo, AsignarJugadorAEquipo $asignarJugador): JsonResponse
    {
        $jugador = $asignarJugador->ejecutar(
            Identificador::desdeTexto($equipo),
            (string) $solicitud->input('matricula'),
            (string) $solicitud->input('posicion'),
        );

        return Respuesta::conDatos((new JugadorConsultadoRecurso($jugador))->resolve($solicitud), estado: 201);
    }

    public function cambiarPosicion(SolicitudDeCambioDePosicion $solicitud, string $equipo, string $jugador, CambiarPosicionDeJugador $cambiarPosicion): JsonResponse
    {
        try {
            $jugadorId = Identificador::desdeTexto($jugador);
        } catch (InvalidArgumentException) {
            throw new JugadorNoEncontrado;
        }

        $actualizado = $cambiarPosicion->ejecutar(Identificador::desdeTexto($equipo), $jugadorId, (string) $solicitud->input('posicion'));

        return Respuesta::conDatos((new JugadorConsultadoRecurso($actualizado))->resolve($solicitud));
    }
}
