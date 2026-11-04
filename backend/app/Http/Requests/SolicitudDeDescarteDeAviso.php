<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\ComprobarAvisoDelEntrenador;

/** Un aviso ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). */
class SolicitudDeDescarteDeAviso extends FormRequest
{
    public function authorize(ComprobarAvisoDelEntrenador $comprobarAviso): bool
    {
        $comprobarAviso->ejecutar(IdentificadorDeRuta::desde((string) $this->route('aviso')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nota_descarte' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['nota_descarte']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
