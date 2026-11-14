<?php

namespace App\Http\Controllers;

use App\Http\Resources\TipoDePruebaRecurso;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\CondicionFisica\Aplicacion\ListarTiposDePrueba;
use Src\CondicionFisica\Dominio\TipoDePrueba;

class TipoDePruebaController extends Controller
{
    /** Sin paginar: no lee `pagina` ni `por_pagina` (§6.1, DA-63). */
    public function listar(Request $solicitud, ListarTiposDePrueba $listarTipos): JsonResponse
    {
        return Respuesta::conDatos(array_map(
            fn (TipoDePrueba $tipo) => (new TipoDePruebaRecurso($tipo))->resolve($solicitud),
            $listarTipos->ejecutar(),
        ));
    }
}
