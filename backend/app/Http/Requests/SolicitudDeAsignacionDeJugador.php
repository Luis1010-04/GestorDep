<?php

namespace App\Http\Requests;

class SolicitudDeAsignacionDeJugador extends SolicitudDeJugadorDeEquipo
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'matricula' => ['required', 'string', 'max:30'],
            'posicion' => ['required', 'string', 'max:20'],
        ];
    }
}
