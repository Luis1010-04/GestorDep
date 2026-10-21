<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Convierte cualquier excepcion de la api en el formato de error {codigo, mensaje, errores}
 * (docs/convenciones-api.md, seccion 3). Nunca incluye trazas, rutas de archivo ni SQL.
 */
final class FormatoDeError
{
    private const CODIGOS_HTTP = [
        401 => 'no_autenticado',
        403 => 'sin_permiso',
        404 => 'recurso_no_encontrado',
        405 => 'metodo_no_permitido',
        409 => 'conflicto',
        422 => 'validacion_fallida',
        500 => 'error_interno',
        503 => 'servicio_no_disponible',
    ];

    public static function responder(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => self::respuesta(422, 'validacion_fallida', self::erroresDeCampo($e)),
            $e instanceof ReglaDeNegocioViolada => self::reglaDeNegocio($e),
            $e instanceof AuthenticationException => self::respuesta(401, 'no_autenticado'),
            $e instanceof ServicioNoDisponible => self::respuesta(503, 'servicio_no_disponible'),
            // Se conservan las cabeceras de la excepcion, como Allow en el 405 o Retry-After.
            $e instanceof HttpExceptionInterface => self::respuesta(
                $e->getStatusCode(),
                self::CODIGOS_HTTP[$e->getStatusCode()] ?? 'solicitud_no_valida',
            )->withHeaders($e->getHeaders()),
            default => self::respuesta(500, 'error_interno'),
        };
    }

    private static function reglaDeNegocio(ReglaDeNegocioViolada $e): JsonResponse
    {
        $clave = 'errores.reglas.'.$e->codigo();
        $mensaje = Lang::has($clave) ? __($clave) : __('errores.regla_de_negocio_violada');

        return self::json(422, $e->codigo(), $mensaje, []);
    }

    /**
     * @param  list<array{campo: string, codigo: string, mensaje: string}>  $errores
     */
    private static function respuesta(int $estado, string $codigo, array $errores = []): JsonResponse
    {
        return self::json($estado, $codigo, __('errores.'.$codigo), $errores);
    }

    /**
     * @param  list<array{campo: string, codigo: string, mensaje: string}>  $errores
     */
    private static function json(int $estado, string $codigo, mixed $mensaje, array $errores): JsonResponse
    {
        return new JsonResponse(['codigo' => $codigo, 'mensaje' => $mensaje, 'errores' => $errores], $estado);
    }

    /**
     * El codigo de cada campo es el nombre de la regla que fallo, en snake_case.
     *
     * @return list<array{campo: string, codigo: string, mensaje: string}>
     */
    private static function erroresDeCampo(ValidationException $e): array
    {
        $reglasFallidas = $e->validator->failed();

        $errores = [];
        foreach ($e->errors() as $campo => $mensajes) {
            $reglas = array_keys($reglasFallidas[$campo] ?? []);
            foreach (array_values($mensajes) as $indice => $mensaje) {
                $errores[] = [
                    'campo' => $campo,
                    'codigo' => isset($reglas[$indice]) ? Str::snake(class_basename($reglas[$indice])) : 'validacion_fallida',
                    'mensaje' => $mensaje,
                ];
            }
        }

        return $errores;
    }
}
