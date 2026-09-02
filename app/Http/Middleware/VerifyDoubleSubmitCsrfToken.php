<?php

declare(strict_types=1);

/**
 * Protege operaciones de escritura mediante una cookie CSRF de doble envío.
 */

namespace App\Http\Middleware;

use App\Domain\Authentication\Exceptions\CsrfTokenMismatchException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que Axios replique el secreto CSRF de la cookie en una cabecera.
 */
final class VerifyDoubleSubmitCsrfToken
{
    /**
     * Valida el token CSRF de los métodos que pueden modificar estado.
     *
     * @param  Request  $request  Petición cuya cookie XSRF-TOKEN debe coincidir con la cabecera X-XSRF-TOKEN.
     * @param  Closure(Request): Response  $next  Siguiente manejador de la cadena, invocado solo si la petición supera el middleware.
     *
     * @throws CsrfTokenMismatchException Cuando falta el token o no coincide.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $cookieName = config('auth_tokens.csrf_cookie.name');
        $headerName = config('auth_tokens.csrf_cookie.header');
        $cookieToken = is_string($cookieName) ? $request->cookie($cookieName) : null;
        $headerToken = is_string($headerName) ? $request->header($headerName) : null;

        if (! is_string($cookieToken)
            || $cookieToken === ''
            || ! is_string($headerToken)
            || ! hash_equals($cookieToken, $headerToken)
        ) {
            throw new CsrfTokenMismatchException('El token CSRF no coincide.');
        }

        return $next($request);
    }
}
