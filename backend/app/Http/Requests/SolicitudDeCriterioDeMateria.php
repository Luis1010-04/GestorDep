<?php

namespace App\Http\Requests;

use App\Http\Respuestas\IdentificadorDeRuta;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\Academico\Aplicacion\ComprobarMateriaDelDocente;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Una materia ajena, inexistente o con identificador que no es UUID v7 da 404 antes de validar el cuerpo, para que ni los
 * errores de validación den pistas (DD-50). Los decimales viajan como cadena con hasta dos decimales (DD-51); el rango
 * de 0 a 10 lo valida el caso de uso (`calificacion_fuera_de_rango`).
 */
class SolicitudDeCriterioDeMateria extends FormRequest
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
            'calificacion_minima' => ['required', 'string', 'regex:/^-?\d+(\.\d{1,2})?$/'],
        ];
    }
}
