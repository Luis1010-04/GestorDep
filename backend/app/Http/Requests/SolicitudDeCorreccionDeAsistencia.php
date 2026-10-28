<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Src\Academico\Aplicacion\ComprobarAsistenciaDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Una asistencia ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo (DD-50).
 * Solo se corrige el estado: cualquier otro campo se rechaza señalándolo (`prohibited`).
 */
class SolicitudDeCorreccionDeAsistencia extends FormRequest
{
    public function authorize(ComprobarAsistenciaDelDocente $comprobarAsistencia, UsuarioActual $usuarioActual): bool
    {
        $docente = $usuarioActual->identificador() ?? throw new AuthenticationException;

        if ($comprobarAsistencia->ejecutar(IdentificadorDeRuta::desde((string) $this->route('asistencia')), $docente) === null) {
            throw new NotFoundHttpException;
        }

        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', 'in:presente,falta,falta_justificada'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validador): void {
                // `prohibited` de Laravel deja pasar los valores vacíos; aquí cualquier presencia del campo se rechaza.
                foreach (array_diff(array_keys($this->all()), ['estado']) as $campo) {
                    $validador->addFailure((string) $campo, 'Prohibited');
                }
            },
        ];
    }
}
