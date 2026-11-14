<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\ComprobarJugadorVisible;

/**
 * Un jugador fuera del alcance, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo
 * (§6.1). La fecha puede ser futura (DA-23). Si el jugador está en baja, el 409 llega después del 422 (DA-24).
 */
class SolicitudDeNuevoAvisoDeLesion extends FormRequest
{
    private const CAMPOS = ['nota', 'fecha', 'reportado_por_jugador'];

    public function authorize(ComprobarJugadorVisible $comprobarJugador): bool
    {
        $comprobarJugador->ejecutar(IdentificadorDeRuta::desde((string) $this->route('jugador')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'nota' => ['required', 'string', 'max:500'],
            'fecha' => ['required', 'string', 'date_format:Y-m-d'],
            'reportado_por_jugador' => ['sometimes', 'boolean:strict'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), self::CAMPOS) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
