<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeUmbral;
use App\Http\Resources\UmbralRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Rendimiento\Aplicacion\ConsultarUmbral;
use Src\Rendimiento\Aplicacion\FijarUmbral;

class UmbralController extends Controller
{
    public function consultar(Request $solicitud, string $equipo, string $jugador, ConsultarUmbral $consultarUmbral): JsonResponse
    {
        $umbral = $consultarUmbral->ejecutar(IdentificadorDeRuta::desde($equipo), IdentificadorDeRuta::desde($jugador));

        return Respuesta::conDatos((new UmbralRecurso($umbral))->resolve($solicitud));
    }

    public function fijar(SolicitudDeUmbral $solicitud, string $equipo, string $jugador, FijarUmbral $fijarUmbral): JsonResponse
    {
        /** @var array{plantilla_id?: string, calificacion_minima?: string, ventana_partidos?: int} $datos */
        $datos = $solicitud->validated();

        $umbral = $fijarUmbral->ejecutar(IdentificadorDeRuta::desde($equipo), IdentificadorDeRuta::desde($jugador), $datos);

        return Respuesta::conDatos((new UmbralRecurso($umbral))->resolve($solicitud));
    }
}
