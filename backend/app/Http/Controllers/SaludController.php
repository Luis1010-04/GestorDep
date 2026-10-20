<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SaludController extends Controller
{
    public function __invoke(): JsonResponse
    {
        DB::select('select 1');

        return response()->json([
            'datos' => [
                'aplicacion' => 'ok',
                'base_de_datos' => 'ok',
            ],
        ]);
    }
}
