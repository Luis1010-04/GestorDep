<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeNuevaAsignatura extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'clave' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:150'],
            'grado' => ['required', 'integer', 'between:1,12'],
        ];
    }
}
