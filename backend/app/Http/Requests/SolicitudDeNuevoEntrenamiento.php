<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Src\Competencia\Aplicacion\ComprobarEquipoEntrenado;

/** Un equipo ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). */
class SolicitudDeNuevoEntrenamiento extends FormRequest
{
    /** ISO 8601 con zona: desplazamiento (`-06:00`) o `Z` (docs/convenciones-api.md §1). */
    public const INSTANTE = 'date_format:Y-m-d\TH:i:sP,Y-m-d\TH:i:s\Z';

    public function authorize(ComprobarEquipoEntrenado $comprobarEquipo): bool
    {
        $comprobarEquipo->ejecutar(IdentificadorDeRuta::desde((string) $this->route('equipo')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'inicia_en' => ['required', 'string', self::INSTANTE],
            'termina_en' => ['required', 'string', self::INSTANTE, 'after:inicia_en'],
            'lugar_id' => ['required', 'string', 'uuid'],
            'responsable_id' => ['required', 'string', 'uuid'],
        ];
    }
}
