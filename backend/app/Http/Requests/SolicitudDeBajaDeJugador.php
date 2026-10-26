<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use Src\Compartido\Dominio\Identificador;
use Src\Plantel\Aplicacion\JugadorNoEncontrado;
use Src\Plantel\Aplicacion\VerJugador;

/**
 * Un jugador ajeno, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo, para que ni
 * los errores de validación den pistas. El motivo vacío no se valida aquí: lo rechaza el caso de uso con `motivo_obligatorio`.
 */
class SolicitudDeBajaDeJugador extends FormRequest
{
    /** [Decisión por defecto] Tope del motivo; la especificación no lo fija. */
    private const MOTIVO_MAXIMO = 500;

    public function authorize(VerJugador $verJugador): bool
    {
        try {
            $verJugador->ejecutar(Identificador::desdeTexto((string) $this->route('jugador')));
        } catch (InvalidArgumentException) {
            throw new JugadorNoEncontrado;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:'.self::MOTIVO_MAXIMO],
            'fecha' => ['required', 'string', 'date_format:Y-m-d'],
        ];
    }
}
