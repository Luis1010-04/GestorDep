<?php

namespace App\Http\Controllers;

use App\Exceptions\ServicioNoDisponible;
use App\Http\Respuestas\Respuesta;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SaludController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
        } catch (QueryException $e) {
            throw new ServicioNoDisponible('base_de_datos', previous: $e);
        }

        return Respuesta::conDatos([
            'aplicacion' => 'ok',
            'base_de_datos' => 'ok',
        ]);
    }
}
