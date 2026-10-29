<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarPartidoDelEntrenador;

/**
 * Un partido ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). Los
 * goles son enteros JSON; el rango de 0 a 99 lo valida el dominio después del estado (DA-33).
 */
class SolicitudDeResultado extends FormRequest
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
            'goles_favor' => ['required', 'integer:strict'],
            'goles_contra' => ['required', 'integer:strict'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['goles_favor', 'goles_contra']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
