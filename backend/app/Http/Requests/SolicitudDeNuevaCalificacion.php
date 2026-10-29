<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\Academico\Aplicacion\ComprobarInscripcionDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Una inscripción ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo, con
 * cualquier cuerpo (DD-50). El valor viaja como cadena con hasta dos decimales (DD-51); el rango de 0 a 10 lo valida el
 * caso de uso (`calificacion_fuera_de_rango`).
 */
class SolicitudDeNuevaCalificacion extends FormRequest
{
    public function authorize(ComprobarInscripcionDelDocente $comprobarInscripcion, UsuarioActual $usuarioActual): bool
    {
        $docente = $usuarioActual->identificador() ?? throw new AuthenticationException;

        if (! $comprobarInscripcion->ejecutar(IdentificadorDeRuta::desde((string) $this->route('inscripcion')), $docente)) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', 'in:parcial,final'],
            'numero_parcial' => ['required_if:tipo,parcial', 'prohibited_if:tipo,final', 'integer', 'between:1,10'],
            'valor' => ['required', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
            'fecha' => ['sometimes', 'string', 'date_format:Y-m-d'],
        ];
    }
}
