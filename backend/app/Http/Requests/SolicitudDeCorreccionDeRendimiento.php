<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Rendimiento\Aplicacion\BuscarRendimientoDelRegistrante;

/**
 * Un rendimiento ajeno, inexistente o con identificador que no es UUID v7 da el mismo 404 antes de validar el cuerpo
 * (§6.1, RN-14). Se acepta cualquiera de los campos de la captura salvo `jugador_id` (§6.2); `metricas` reemplaza la
 * lista. Un campo no admitido se rechaza señalándolo, y el cuerpo vacío también.
 */
class SolicitudDeCorreccionDeRendimiento extends FormRequest
{
    public function authorize(BuscarRendimientoDelRegistrante $buscarRendimiento): bool
    {
        $buscarRendimiento->ejecutar(IdentificadorDeRuta::desde((string) $this->route('rendimiento')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return SolicitudDeRendimiento::reglasDeCaptura('sometimes');
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                $campos = array_keys($this->all());

                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff($campos, array_diff(SolicitudDeRendimiento::CAMPOS, ['jugador_id'])) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
                if ($campos === []) {
                    $validador->errors()->add('cuerpo', __('errores.cuerpo_sin_campos_rendimiento'));
                }
            },
        ];
    }
}
