<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Src\Compartido\Aplicacion\UsuarioActual;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** `rol:entrenador` o `rol:entrenador,docente`: deja pasar al usuario con alguno de los roles; si no, 403 `sin_permiso`. */
final class ExigirRol
{
    public function __construct(private readonly UsuarioActual $usuarioActual) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($this->usuarioActual->identificador() === null) {
            throw new AuthenticationException;
        }

        if (array_intersect($roles, $this->usuarioActual->roles()) === []) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
