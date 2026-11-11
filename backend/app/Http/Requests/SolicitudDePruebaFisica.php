<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\CondicionFisica\Aplicacion\ComprobarJugadorDelEntrenador;

/**
 * Un jugador fuera del alcance, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo
 * (§6.1). Aquí solo se valida la forma; tipo y rango los decide el caso de uso, después de la baja y del equipo (DA-58).
 */
class SolicitudDePruebaFisica extends FormRequest
{
    private const CAMPOS = ['equipo_id', 'tipo_prueba_id', 'resultado', 'fecha', 'es_correccion'];

    public function authorize(ComprobarJugadorDelEntrenador $comprobarJugador): bool
    {
        $comprobarJugador->ejecutar(IdentificadorDeRuta::desde((string) $this->route('jugador')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $reglas = [
            'equipo_id' => ['required', 'string', 'uuid'],
            'tipo_prueba_id' => ['required', 'string', 'uuid'],
            'resultado' => ['required', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
            'fecha' => ['sometimes', 'string', 'date_format:Y-m-d'],
            'es_correccion' => ['sometimes', 'boolean:strict'],
        ];

        return $this->corrige() ? [...$reglas, 'motivo' => ['required', 'string', 'max:255']] : $reglas;
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                // `motivo` solo se admite con `es_correccion` (DA-39).
                $permitidos = $this->corrige() ? [...self::CAMPOS, 'motivo'] : self::CAMPOS;
                foreach (array_diff(array_keys($this->all()), $permitidos) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }

    private function corrige(): bool
    {
        return $this->input('es_correccion') === true;
    }
}
