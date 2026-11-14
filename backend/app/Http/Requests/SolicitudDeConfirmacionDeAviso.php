<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Salud\Aplicacion\ComprobarAvisoDelEntrenador;

/**
 * Un aviso ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). Tipo, zona,
 * causa y fecha estimada son obligatorios salvo con `apto`, que prohíbe esos campos y la nota (DD-57). Solo un
 * entrenador del jugador confirma (DD-61).
 */
class SolicitudDeConfirmacionDeAviso extends FormRequest
{
    private const CAMPOS_DE_LA_LESION = ['tipo', 'zona', 'causa', 'fecha_estimada_regreso', 'nota'];

    public function authorize(ComprobarAvisoDelEntrenador $comprobarAviso): bool
    {
        $comprobarAviso->ejecutar(IdentificadorDeRuta::desde((string) $this->route('aviso')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $presencia = $this->input('estado') === 'apto' ? 'sometimes' : 'required';

        return [
            'estado' => ['required', 'string', 'in:apto,con_molestia,lesionado'],
            'tipo' => [$presencia, 'string', 'max:100'],
            'zona' => [$presencia, 'string', 'max:100'],
            'causa' => [$presencia, 'string', 'in:entrenamiento,partido,otra'],
            'fecha_estimada_regreso' => [$presencia, 'string', 'date_format:Y-m-d'],
            'nota' => ['sometimes', 'string', 'max:500'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, ['estado', ...self::CAMPOS_DE_LA_LESION]) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($this->input('estado') === 'apto') {
                    foreach (array_intersect($campos, self::CAMPOS_DE_LA_LESION) as $campo) {
                        $validador->addFailure((string) $campo, 'Prohibited');
                    }
                }
            },
        ];
    }
}
