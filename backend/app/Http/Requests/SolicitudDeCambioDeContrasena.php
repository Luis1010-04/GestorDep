<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** La longitud mínima de la contraseña nueva la valida PoliticaDeContrasena, no esta solicitud. */
class SolicitudDeCambioDeContrasena extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'contrasena_actual' => ['required', 'string'],
            'contrasena_nueva' => ['required', 'string'],
        ];
    }
}
