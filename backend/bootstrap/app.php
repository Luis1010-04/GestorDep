<?php

use App\Exceptions\FormatoDeError;
use App\Http\Middleware\ExigirRol;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->redirectGuestsTo('/ingreso');
        $middleware->redirectUsersTo('/panel');
        $middleware->alias(['rol' => ExigirRol::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Toda la api responde con el formato de error de docs/convenciones-api.md.
        // Fuera de la api, un error interno nunca muestra la pagina de depuracion (trazas, rutas, mensajes), ni con APP_DEBUG=true.
        // Se dejan al manejador de Laravel las excepciones HTTP (paginas 404, 419...) y las que se resuelven con una redireccion.
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return FormatoDeError::responder($e);
            }

            $conTratamientoPropio = $e instanceof HttpExceptionInterface
                || $e instanceof HttpResponseException
                || $e instanceof AuthenticationException
                || $e instanceof ValidationException;

            return $conTratamientoPropio ? null : FormatoDeError::responder($e);
        });
    })->create();
