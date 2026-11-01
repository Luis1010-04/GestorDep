<?php

namespace App\Http\Controllers;

use App\Http\Resources\DesempenoRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Rendimiento\Aplicacion\ConsultarDesempeno;

class DesempenoController extends Controller
{
    public function consultar(Request $solicitud, string $equipo, string $jugador, ConsultarDesempeno $consultarDesempeno): JsonResponse
    {
        $desempeno = $consultarDesempeno->ejecutar(IdentificadorDeRuta::desde($equipo), IdentificadorDeRuta::desde($jugador));

        return Respuesta::conDatos((new DesempenoRecurso($desempeno))->resolve($solicitud));
    }
}
