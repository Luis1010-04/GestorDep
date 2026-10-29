<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeNuevoPeriodo extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'fecha_inicio' => ['required', 'string', 'date_format:Y-m-d'],
            'fecha_fin' => ['required', 'string', 'date_format:Y-m-d'],
        ];
    }
}
