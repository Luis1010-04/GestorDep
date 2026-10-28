<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\Academico\Aplicacion\ComprobarInscripcionDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Una inscripción ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (DD-50). */
class SolicitudDeNuevaAsistencia extends FormRequest
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
            'estado' => ['required', 'string', 'in:presente,falta,falta_justificada'],
            'fecha' => ['sometimes', 'string', 'date_format:Y-m-d'],
        ];
    }
}
