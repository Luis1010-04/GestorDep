<?php

namespace App\Http\Respuestas;

use Illuminate\Http\JsonResponse;

/** Respuesta con `datos` y, si las hay, `advertencias` (docs/convenciones-api.md, seccion 4). */
final class Respuesta
{
    /**
     * @param  list<array{codigo: string, mensaje: string, campo?: string}>  $advertencias  el mensaje sale de lang/es; `campo` es
     *                                                                                      opcional (convenciones §4 v1.3, DA-15)
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
