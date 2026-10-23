<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeCambioDeContrasena;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Src\Compartido\Dominio\Identificador;
use Src\Identidad\Aplicacion\CambiarContrasena;

class ContrasenaController extends Controller
{
    public function cambiar(SolicitudDeCambioDeContrasena $solicitud, CambiarContrasena $cambiarContrasena): Response
    {
        $cambiarContrasena->ejecutar(
            Identificador::desdeTexto((string) Auth::guard('web')->id()),
            (string) $solicitud->input('contrasena_actual'),
            (string) $solicitud->input('contrasena_nueva'),
            $solicitud->session()->getId(),
        );

        // Sanctum guarda en la sesión el hash del usuario cargado en la petición; se recarga para que la sesión actual siga válida.
        $guardia = Auth::guard('web');
        $guardia->forgetUser();
        $guardia->user();

        return response()->noContent();
    }
}
