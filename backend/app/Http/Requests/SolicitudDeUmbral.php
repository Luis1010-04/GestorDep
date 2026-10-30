<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Rendimiento\Aplicacion\ComprobarJugadorDelEquipo;

/**
 * Un equipo que el usuario no entrena, o un jugador fuera de su alcance o sin posición en el equipo, da 404 antes de
 * validar el cuerpo (§6.1). La calificación y la ventana van juntas; con `plantilla_id` dan umbral_ambiguo, que decide
 * el caso de uso (v1.0.4 §6.2).
 */
class SolicitudDeUmbral extends FormRequest
{
    private const CAMPOS = ['plantilla_id', 'calificacion_minima', 'ventana_partidos'];

    public function authorize(ComprobarJugadorDelEquipo $comprobar): bool
    {
        $comprobar->ejecutar(
            IdentificadorDeRuta::desde((string) $this->route('equipo')),
            IdentificadorDeRuta::desde((string) $this->route('jugador')),
        );

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'plantilla_id' => ['sometimes', 'string', 'uuid'],
            'calificacion_minima' => ['required_with:ventana_partidos', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
            'ventana_partidos' => ['required_with:calificacion_minima', 'integer:strict'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                foreach (array_diff(array_keys($this->all()), self::CAMPOS) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
