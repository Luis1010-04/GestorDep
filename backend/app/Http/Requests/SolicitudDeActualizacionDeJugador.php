<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\ComprobarJugadorEscribible;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;

/**
 * Un jugador ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo, para que ni
 * los errores de validación den pistas. Lanza la excepción de aplicación, nunca un 403. Solo se aceptan los campos
 * del expediente: cualquier otro se rechaza señalándolo (a diferencia de la posición en un equipo, que los ignora).
 */
class SolicitudDeActualizacionDeJugador extends FormRequest
{
    public function authorize(ComprobarJugadorEscribible $comprobarJugador): bool
    {
        try {
            $comprobarJugador->ejecutar(Identificador::desdeTexto((string) $this->route('jugador')));
        } catch (InvalidArgumentException) {
            throw new JugadorNoEncontrado;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombres' => ['sometimes', 'required', 'string', 'max:120'],
            'apellidos' => ['sometimes', 'required', 'string', 'max:120'],
            'fecha_nacimiento' => ['sometimes', 'required', 'string', 'date_format:Y-m-d'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, ['nombres', 'apellidos', 'fecha_nacimiento']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($campos === []) {
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos'));
                }
            },
        ];
    }
}
