<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolicitudDeInicioDeSesion;
use App\Http\Resources\UsuarioRecurso;
use App\Http\Respuestas\Respuesta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Src\Identidad\Aplicacion\IniciarSesion;

class SesionController extends Controller
{
    public function iniciar(SolicitudDeInicioDeSesion $solicitud, IniciarSesion $iniciarSesion): JsonResponse
    {
        $usuario = $iniciarSesion->ejecutar(
            (string) $solicitud->input('correo'),
            (string) $solicitud->input('contrasena'),
        );

        Auth::guard('web')->loginUsingId($usuario->id()->valor());
        $solicitud->session()->regenerate();

        return Respuesta::conDatos((new UsuarioRecurso($usuario))->resolve($solicitud));
    }
}
