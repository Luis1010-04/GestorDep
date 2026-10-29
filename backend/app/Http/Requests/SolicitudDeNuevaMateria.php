<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeNuevaMateria extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'asignatura_id' => ['required', 'string', 'uuid'],
            'periodo_id' => ['required', 'string', 'uuid'],
        ];
    }
}
