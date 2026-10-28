<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\Academico\Aplicacion\ComprobarMateriaDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Una materia ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo, con cualquier
 * cuerpo (DD-50): ni los errores de validación ni la existencia de una matrícula dan pistas.
 */
class SolicitudDeNuevaInscripcion extends FormRequest
{
    public function authorize(ComprobarMateriaDelDocente $comprobarMateria, UsuarioActual $usuarioActual): bool
    {
        $docente = $usuarioActual->identificador() ?? throw new AuthenticationException;

        if (! $comprobarMateria->ejecutar(IdentificadorDeRuta::desde((string) $this->route('materia')), $docente)) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'matricula' => ['required', 'string', 'max:30'],
        ];
    }
}
