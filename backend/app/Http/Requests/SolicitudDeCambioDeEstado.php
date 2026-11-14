<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\ComprobarJugadorDelEntrenador;

/**
 * Quien no entrena al jugador recibe 404 antes de validar el cuerpo (§6.1). Se acepta `estado` (`con_molestia` o
 * `lesionado`) y `fecha_estimada_regreso`, al menos uno; sin campos responde 422 (plan de T03). La fecha puede ser futura.
 */
class SolicitudDeCambioDeEstado extends FormRequest
{
    public function authorize(ComprobarJugadorDelEntrenador $comprobarJugador): bool
    {
        $comprobarJugador->ejecutar(IdentificadorDeRuta::desde((string) $this->route('jugador')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'estado' => ['sometimes', 'string', 'in:con_molestia,lesionado'],
            'fecha_estimada_regreso' => ['sometimes', 'string', 'date_format:Y-m-d'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, ['estado', 'fecha_estimada_regreso']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($campos === []) {
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos_estado'));
                }
            },
        ];
    }
}
