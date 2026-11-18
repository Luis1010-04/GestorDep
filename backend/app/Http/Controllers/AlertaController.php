<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Consultas\Aplicacion\AlertaVisible;
use Src\Consultas\Aplicacion\ConsultarAlertasDelJugador;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AlertaController extends Controller
{
    /** Un jugador fuera del alcance, en baja, inexistente o con un id que no es UUID v7 responde el mismo 404 (RNF-01, RN-23). */
    public function delJugador(Request $solicitud, string $jugador, ConsultarAlertasDelJugador $consultar): JsonResponse
    {
        $alertas = $consultar->ejecutar(IdentificadorDeRuta::desde($jugador)) ?? throw new NotFoundHttpException;

        return Respuesta::conDatos(array_map(
            fn (AlertaVisible $alerta): array => (new AlertaRecurso($alerta))->resolve($solicitud),
            $alertas,
        ));
    }
}
