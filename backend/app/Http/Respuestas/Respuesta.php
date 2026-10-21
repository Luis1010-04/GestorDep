<?php

namespace App\Http\Respuestas;

use Illuminate\Http\JsonResponse;

/** Respuesta con `datos` y, si las hay, `advertencias` (docs/convenciones-api.md, seccion 4). */
final class Respuesta
{
    /**
     * @param  list<array{codigo: string, mensaje: string}>  $advertencias  el mensaje sale de lang/es
     */
    public static function conDatos(mixed $datos, array $advertencias = [], int $estado = 200): JsonResponse
    {
        $cuerpo = ['datos' => $datos];
        if ($advertencias !== []) {
            $cuerpo['advertencias'] = $advertencias;
        }

        return new JsonResponse($cuerpo, $estado);
    }
}
