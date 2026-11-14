<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeAltaDeJugador;
use App\Http\Requests\SolicitudDeCambioDeEstado;
use App\Http\Resources\EstadoDeDisponibilidadRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Salud\Aplicacion\CambiarEstado;
use Src\Salud\Aplicacion\ComprobarJugadorVisible;
use Src\Salud\Aplicacion\ConsultarEstado;
use Src\Salud\Aplicacion\DarDeAlta;

class EstadoDeDisponibilidadController extends ControladorDeSalud
{
    public function consultar(Request $solicitud, string $jugador, ComprobarJugadorVisible $comprobarJugador, ConsultarEstado $consultarEstado): JsonResponse
    {
        $jugadorId = IdentificadorDeRuta::desde($jugador);
        $comprobarJugador->ejecutar($jugadorId);

        return Respuesta::conDatos((new EstadoDeDisponibilidadRecurso($consultarEstado->ejecutar($jugadorId)))->resolve($solicitud));
    }

    public function cambiar(SolicitudDeCambioDeEstado $solicitud, string $jugador, CambiarEstado $cambiarEstado): JsonResponse
    {
        /** @var array{estado?: string, fecha_estimada_regreso?: string} $datos */
        $datos = $solicitud->validated();

        return $this->conMensajesDeSalud(function () use ($solicitud, $jugador, $cambiarEstado, $datos): JsonResponse {
            $estado = $cambiarEstado->ejecutar(IdentificadorDeRuta::desde($jugador), $datos['estado'] ?? null, $datos['fecha_estimada_regreso'] ?? null);

            return Respuesta::conDatos((new EstadoDeDisponibilidadRecurso($estado))->resolve($solicitud));
        });
    }

    public function darDeAlta(SolicitudDeAltaDeJugador $solicitud, string $jugador, DarDeAlta $darDeAlta): JsonResponse
    {
        /** @var array{fecha_alta?: string} $datos */
        $datos = $solicitud->validated();

        return $this->conMensajesDeSalud(function () use ($solicitud, $jugador, $darDeAlta, $datos): JsonResponse {
            $estado = $darDeAlta->ejecutar(IdentificadorDeRuta::desde($jugador), $datos['fecha_alta'] ?? null);

            return Respuesta::conDatos((new EstadoDeDisponibilidadRecurso($estado))->resolve($solicitud));
        });
    }
}
