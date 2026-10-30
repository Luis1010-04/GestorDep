<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;

/**
 * Un partido ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). La
 * lista puede estar vacía (DA-32); un jugador repetido o un campo no admitido se rechazan.
 */
class SolicitudDeAlineacion extends FormRequest
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
            'alineacion' => ['present', 'array', 'list'],
            'alineacion.*' => ['array:jugador_id,posicion_id,rol_alineacion'],
            'alineacion.*.jugador_id' => ['required', 'string', 'uuid', 'distinct:ignore_case'],
            'alineacion.*.posicion_id' => ['required', 'string', 'uuid'],
            'alineacion.*.rol_alineacion' => ['required', 'string', 'in:titular,suplente'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['alineacion']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
