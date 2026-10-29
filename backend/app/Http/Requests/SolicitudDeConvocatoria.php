<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;

/**
 * Un partido ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). Solo
 * se acepta `jugador_id` (§6.2): la posición y el rol llegan con la alineación (T07).
 */
class SolicitudDeConvocatoria extends FormRequest
{
    public function authorize(BuscarPartidoDelEntrenador $buscarPartido): bool
    {
        $buscarPartido->ejecutar(IdentificadorDeRuta::desde((string) $this->route('partido')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'jugador_id' => ['required', 'string', 'uuid'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['jugador_id']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
