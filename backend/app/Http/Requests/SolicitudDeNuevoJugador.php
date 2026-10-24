<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitudDeNuevoJugador extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'fecha_nacimiento' => ['required', 'string', 'date_format:Y-m-d'],
            'matricula' => ['required', 'string', 'max:30'],
            'equipo_id' => ['required', 'string', 'uuid:7'],
            'posicion' => ['required', 'string', 'max:20'],
        ];
    }
}
