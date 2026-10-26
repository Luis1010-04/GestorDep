<?php

namespace App\Http\Requests;

class SolicitudDeCambioDePosicion extends SolicitudDeJugadorDeEquipo
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'posicion' => ['required', 'string', 'max:20'],
        ];
    }
}
