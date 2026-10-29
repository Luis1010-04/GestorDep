<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Foundation\Http\FormRequest;
use Src\Competencia\Aplicacion\BuscarEntrenamientoDelEntrenador;

/** Un entrenamiento ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (§6.1). */
class SolicitudDeAsistenciasDeEntrenamiento extends FormRequest
{
    public function authorize(BuscarEntrenamientoDelEntrenador $buscarEntrenamiento): bool
    {
        $buscarEntrenamiento->ejecutar(IdentificadorDeRuta::desde((string) $this->route('entrenamiento')));

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'registros' => ['required', 'array', 'list'],
            'registros.*' => ['array:jugador_id,estado'],
            'registros.*.jugador_id' => ['required', 'string', 'uuid', 'distinct'],
            'registros.*.estado' => ['required', 'string', 'in:presente,falta,falta_justificada'],
        ];
    }
}
