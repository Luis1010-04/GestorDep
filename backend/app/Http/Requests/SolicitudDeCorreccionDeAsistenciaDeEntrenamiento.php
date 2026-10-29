<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Competencia\Aplicacion\BuscarAsistenciaDelEntrenador;

/**
 * Un registro de un equipo ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo
 * (§6.1). Solo se acepta `estado`: cualquier otro campo se rechaza señalándolo.
 */
class SolicitudDeCorreccionDeAsistenciaDeEntrenamiento extends FormRequest
{
    public function authorize(BuscarAsistenciaDelEntrenador $buscarAsistencia): bool
    {
        $buscarAsistencia->ejecutar(IdentificadorDeRuta::desde((string) $this->route('asistencia')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', 'in:presente,falta,falta_justificada'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['estado']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
