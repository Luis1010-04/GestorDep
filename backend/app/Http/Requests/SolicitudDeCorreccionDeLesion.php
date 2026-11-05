<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\BuscarLesionDelRegistrante;

/**
 * Una lesión ajena, inexistente o con identificador que no es UUID v7 da el mismo 404 antes de validar el cuerpo (RN-15).
 * Se acepta `tipo`, `zona`, `causa` y `nota`, al menos uno; la fecha estimada no se cambia aquí (DA-02).
 */
class SolicitudDeCorreccionDeLesion extends FormRequest
{
    private const CAMPOS = ['tipo', 'zona', 'causa', 'nota'];

    public function authorize(BuscarLesionDelRegistrante $buscarLesion): bool
    {
        $buscarLesion->ejecutar(IdentificadorDeRuta::desde((string) $this->route('lesion')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'string', 'max:100'],
            'zona' => ['sometimes', 'string', 'max:100'],
            'causa' => ['sometimes', 'string', 'in:entrenamiento,partido,otra'],
            'nota' => ['sometimes', 'string', 'max:500'],
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
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos_lesion'));
                }
            },
        ];
    }
}
