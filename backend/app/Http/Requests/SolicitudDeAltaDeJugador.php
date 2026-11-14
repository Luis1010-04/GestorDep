<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\ComprobarJugadorDelEntrenador;

/** Quien no entrena al jugador recibe 404 antes de validar el cuerpo (§6.1). `fecha_alta` es opcional y puede ser futura. */
class SolicitudDeAltaDeJugador extends FormRequest
{
    public function authorize(ComprobarJugadorDelEntrenador $comprobarJugador): bool
    {
        $comprobarJugador->ejecutar(IdentificadorDeRuta::desde((string) $this->route('jugador')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'fecha_alta' => ['sometimes', 'string', 'date_format:Y-m-d'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['fecha_alta']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
