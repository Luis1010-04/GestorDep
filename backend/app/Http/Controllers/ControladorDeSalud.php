<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Src\Compartido\Dominio\ReglaDeNegocioViolada;
use Src\Salud\Aplicacion\JugadorEnBajaSinAviso;
use Src\Salud\Aplicacion\JugadorEnBajaSinCambioDeEstado;
use Src\Salud\Dominio\FechaAnteriorAlInicio;

/**
 * Punto local de los mensajes de Salud (DA-27, plan de T03). `jugador_en_baja` y `rango_de_fechas_invalido` conservan su
 * código, pero el texto de `errores.reglas` habla de otras capturas y no cambia: aquí se elige el propio.
 */
abstract class ControladorDeSalud extends Controller
{
    /** @param  callable(): JsonResponse  $operacion */
    protected function conMensajesDeSalud(callable $operacion): JsonResponse
    {
        try {
            return $operacion();
        } catch (JugadorEnBajaSinAviso $e) {
            return self::regla($e, 'errores.avisos.jugador_en_baja', 409);
        } catch (JugadorEnBajaSinCambioDeEstado $e) {
            return self::regla($e, 'errores.avisos.jugador_en_baja_estado', 409);
        } catch (FechaAnteriorAlInicio $e) {
            return self::regla($e, 'errores.avisos.rango_de_fechas_invalido', 422);
        }
    }

    private static function regla(ReglaDeNegocioViolada $e, string $clave, int $estado): JsonResponse
    {
        return new JsonResponse(['codigo' => $e->codigo(), 'mensaje' => __($clave), 'errores' => []], $estado);
    }
}
