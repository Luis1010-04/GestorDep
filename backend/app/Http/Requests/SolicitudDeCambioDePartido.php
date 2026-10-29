<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;

/**
 * Un partido ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). Se
 * aceptan los campos de alta y `estado = cancelado` (§6.2); `torneo_id: null` quita el torneo. Cualquier otro campo
 * se rechaza señalándolo, y el cuerpo vacío también.
 */
class SolicitudDeCambioDePartido extends FormRequest
{
    public function authorize(BuscarPartidoDelEntrenador $buscarPartido): bool
    {
        $buscarPartido->ejecutar(IdentificadorDeRuta::desde((string) $this->route('partido')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'inicia_en' => ['sometimes', 'required', 'string', SolicitudDeNuevoEntrenamiento::INSTANTE],
            'lugar_id' => ['sometimes', 'required', 'string', 'uuid'],
            'rival_id' => ['sometimes', 'required', 'string', 'uuid'],
            'torneo_id' => ['sometimes', 'nullable', 'string', 'uuid'],
            'tipo' => ['sometimes', 'required', 'string', 'in:amistoso,oficial,interno,torneo'],
            'condicion' => ['sometimes', 'required', 'string', 'in:local,visitante'],
            'estado' => ['sometimes', 'required', 'string', 'in:cancelado'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, [...SolicitudDeNuevoPartido::CAMPOS, 'estado']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($campos === []) {
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos_partido'));
                }
            },
        ];
    }
}
