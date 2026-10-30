<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Rendimiento\Aplicacion\BuscarPartidoDelEntrenador;

/**
 * Un partido ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). Aquí
 * solo se validan los tipos (§6.2); los rangos los valida el dominio con valor_fuera_de_rango (§6.3, DA-11). Los valores
 * de métrica son enteros JSON, porque todas las métricas sembradas son enteras (DA-14).
 */
class SolicitudDeRendimiento extends FormRequest
{
    public const CAMPOS = ['jugador_id', 'minutos', 'fue_titular', 'tarjetas_amarillas', 'tarjetas_rojas', 'calificacion_general', 'observacion', 'metricas'];

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
            ...self::reglasDeCaptura('required'),
        ];
    }

    /**
     * Reglas de tipo de los campos de la captura, compartidas con la corrección.
     *
     * @return array<string, list<string>>
     */
    public static function reglasDeCaptura(string $presencia): array
    {
        return [
            'minutos' => [$presencia, 'integer:strict'],
            'fue_titular' => [$presencia, 'boolean:strict'],
            'tarjetas_amarillas' => [$presencia, 'integer:strict'],
            'tarjetas_rojas' => [$presencia, 'integer:strict'],
            'calificacion_general' => [$presencia, 'integer:strict'],
            'observacion' => ['sometimes', 'nullable', 'string'],
            'metricas' => [$presencia === 'required' ? 'present' : 'sometimes', 'array', 'list'],
            'metricas.*' => ['array:metrica_id,valor'],
            'metricas.*.metrica_id' => ['required', 'string', 'uuid', 'distinct:ignore_case'],
            'metricas.*.valor' => ['required', 'integer:strict'],
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
