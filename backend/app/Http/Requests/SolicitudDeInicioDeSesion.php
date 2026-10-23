<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeInicioDeSesion extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'correo' => ['required', 'string'],
            'contrasena' => ['required', 'string'],
        ];
    }
}
