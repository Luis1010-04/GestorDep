<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\ComprobarEquipoEntrenado;

/**
 * Un equipo ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). La
 * temporada no se recibe: se deriva de la fecha (§3.4). Cualquier campo fuera de §6.2 se rechaza señalándolo.
 */
class SolicitudDeNuevoPartido extends FormRequest
{
    public const CAMPOS = ['inicia_en', 'lugar_id', 'rival_id', 'torneo_id', 'tipo', 'condicion'];

    public function authorize(ComprobarEquipoEntrenado $comprobarEquipo): bool
    {
        $comprobarEquipo->ejecutar(IdentificadorDeRuta::desde((string) $this->route('equipo')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'inicia_en' => ['required', 'string', SolicitudDeNuevoEntrenamiento::INSTANTE],
            'lugar_id' => ['required', 'string', 'uuid'],
            'rival_id' => ['required', 'string', 'uuid'],
            'torneo_id' => ['nullable', 'string', 'uuid'],
            'tipo' => ['required', 'string', 'in:amistoso,oficial,interno,torneo'],
            'condicion' => ['required', 'string', 'in:local,visitante'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), self::CAMPOS) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
