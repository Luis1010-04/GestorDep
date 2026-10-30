<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeNuevoTorneo extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'temporada_id' => ['required', 'string', 'uuid'],
        ];
    }
}
