<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Plantilla propia (HU-15, v1.0.4 §6.2). Aquí solo se validan los tipos; los rangos los valida el dominio con
 * valor_fuera_de_rango. La calificación viaja como cadena con hasta dos decimales (DD-51).
 */
class SolicitudDePlantilla extends FormRequest
{
    private const CAMPOS = ['nombre', 'posicion_id', 'calificacion_minima', 'ventana_partidos'];

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'posicion_id' => ['required', 'string', 'uuid'],
            'calificacion_minima' => ['required', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
            'ventana_partidos' => ['required', 'integer:strict'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                foreach (array_diff(array_keys($this->all()), self::CAMPOS) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
