<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\ComprobarEquipoDelEntrenador;
use Src\Plantel\Aplicacion\EquipoNoEncontrado;

/**
 * Las rutas de un equipo comprueban primero que sea del entrenador: un equipo ajeno da 404 antes de validar los campos,
 * para que ni los errores de validación den pistas. Lanza la excepción de aplicación, nunca un 403.
 */
abstract class SolicitudDeJugadorDeEquipo extends FormRequest
{
    public function authorize(ComprobarEquipoDelEntrenador $comprobarEquipo): bool
    {
        try {
            $comprobarEquipo->ejecutar(Identificador::desdeTexto((string) $this->route('equipo')));
        } catch (InvalidArgumentException) {
            throw new EquipoNoEncontrado;
        }

        return true;
    }
}
