<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

/**
 * Sustituye a `StartSession` en el grupo web (PT-16, auditoría de PT-12 H-02).
 *
 * Una visita GET o HEAD a una página web sin cookie de sesión arranca la sesión en memoria (la necesitan
 * `ShareErrorsFromSession`, el CSRF y el guard), pero no la guarda, no envía su cookie ni corre la recolección de
 * basura: una visita anónima no escribe en `sessions`. Con cookie, el comportamiento es el de `StartSession`.
 * `sanctum/csrf-cookie` no está en la lista porque el ingreso necesita que cree la sesión.
 */
final class IniciarSesionSinGuardarAnonimas extends StartSession
{
    /** @var list<string> */
    private const PAGINAS = ['/', 'ingreso', 'panel'];

    /**
     * @param  Session  $session
     */
    protected function handleStatefulRequest(Request $request, $session, Closure $next): mixed
    {
        if (! $this->esVisitaAnonimaAUnaPagina($request, $session)) {
            return parent::handleStatefulRequest($request, $session, $next);
        }

        $request->setLaravelSession($this->startSession($request, $session));

        return $next($request);
    }

    private function esVisitaAnonimaAUnaPagina(Request $request, Session $session): bool
    {
        $cookie = $request->cookies->get($session->getName());

        return in_array($request->getMethod(), ['GET', 'HEAD'], true)
            && (! is_string($cookie) || $cookie === '')
            && $request->is(...self::PAGINAS);
    }
}
