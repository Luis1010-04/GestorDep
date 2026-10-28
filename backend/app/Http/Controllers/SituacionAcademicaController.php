<?php

namespace App\Http\Controllers;

use App\Http\Resources\SituacionAcademicaRecurso;
use App\Http\Respuestas\IdentificadorDeRuta;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Academico\Aplicacion\VerSituacionAcademica;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SituacionAcademicaController extends Controller
{
    /** Un jugador fuera del alcance, inexistente o con id que no es UUID v7 responde el mismo 404 (RNF-01). */
    public function ver(Request $solicitud, string $jugador, VerSituacionAcademica $verSituacion): JsonResponse
    {
        $situacion = $verSituacion->ejecutar(IdentificadorDeRuta::desde($jugador)) ?? throw new NotFoundHttpException;

        return Respuesta::conDatos((new SituacionAcademicaRecurso($situacion))->resolve($solicitud));
    }
}
