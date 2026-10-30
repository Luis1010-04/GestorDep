<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Alta de un lugar o de un rival (HU-28): solo el nombre. */
class SolicitudDeNombreDeCatalogo extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
        ];
    }
}
