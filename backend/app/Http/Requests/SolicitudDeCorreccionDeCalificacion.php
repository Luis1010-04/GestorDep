<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Academico\Aplicacion\ComprobarCalificacionDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Una calificación ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (DD-50).
 * Solo se corrige el valor: cualquier otro campo se rechaza señalándolo (`prohibited`), como en el expediente de H1.
 */
class SolicitudDeCorreccionDeCalificacion extends FormRequest
{
    public function authorize(ComprobarCalificacionDelDocente $comprobarCalificacion, UsuarioActual $usuarioActual): bool
    {
        $docente = $usuarioActual->identificador() ?? throw new AuthenticationException;

        if ($comprobarCalificacion->ejecutar(IdentificadorDeRuta::desde((string) $this->route('calificacion')), $docente) === null) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'valor' => ['required', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['valor']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
