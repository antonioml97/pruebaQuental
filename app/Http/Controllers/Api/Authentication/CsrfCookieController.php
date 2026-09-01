<?php

declare(strict_types=1);

/**
 * Entrega a clientes de navegador la cookie CSRF esperada por la API.
 */

namespace App\Http\Controllers\Api\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Cookies\AuthenticationCookieFactory;
use Illuminate\Http\Response;
use LogicException;

/**
 * Emite un secreto CSRF aleatorio sin crear una sesión de servidor.
 */
final class CsrfCookieController extends Controller
{
    /**
     * Crea una cookie legible que Axios replicará en la cabecera X-XSRF-TOKEN.
     */
    public function __invoke(AuthenticationCookieFactory $cookies): Response
    {
        $lifetimeMinutes = (int) config('auth_tokens.lifetime_minutes');

        if ($lifetimeMinutes < 1) {
            throw new LogicException('La duración configurada del token debe ser positiva.');
        }

        $token = bin2hex(random_bytes(32));

        return response()
            ->noContent()
            ->withCookie($cookies->csrfToken($token, now()->addMinutes($lifetimeMinutes)));
    }
}
