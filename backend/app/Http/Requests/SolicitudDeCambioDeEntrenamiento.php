<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarEntrenamientoDelEntrenador;

/**
 * Un entrenamiento ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1).
 * Solo se aceptan los campos de §6.2: cualquier otro se rechaza señalándolo, y el cuerpo vacío también.
 */
class SolicitudDeCambioDeEntrenamiento extends FormRequest
{
    private const CAMPOS = ['inicia_en', 'termina_en', 'lugar_id', 'responsable_id', 'estado'];

    public function authorize(BuscarEntrenamientoDelEntrenador $buscarEntrenamiento): bool
    {
        $buscarEntrenamiento->ejecutar(IdentificadorDeRuta::desde((string) $this->route('entrenamiento')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'inicia_en' => ['sometimes', 'required', 'string', SolicitudDeNuevoEntrenamiento::INSTANTE],
            'termina_en' => ['sometimes', 'required', 'string', SolicitudDeNuevoEntrenamiento::INSTANTE],
            'lugar_id' => ['sometimes', 'required', 'string', 'uuid'],
            'responsable_id' => ['sometimes', 'required', 'string', 'uuid'],
            'estado' => ['sometimes', 'required', 'string', 'in:realizado,cancelado'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, self::CAMPOS) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($campos === []) {
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos_entrenamiento'));
                }
            },
        ];
    }
}
