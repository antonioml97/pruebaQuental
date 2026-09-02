<?php

declare(strict_types=1);

/**
 * Autentica peticiones mediante el token opaco almacenado en una cookie.
 */

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Services\Authentication\TokenUsageRecorder;
use App\Services\Authentication\TokenValidator;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extrae la cookie y asocia a la petición la sesión resuelta por el validador.
 */
final class AuthenticateAccessToken
{
    /** Validador independiente del transporte de la credencial. */
    private readonly TokenValidator $tokens;

    /** Registro de actividad separado de la validación del secreto. */
    private readonly TokenUsageRecorder $usage;

    /**
     * Recibe la validación de sesiones y el registro separado de actividad.
     *
     * @param  TokenValidator  $tokens  Validador de la credencial opaca que no registra actividad.
     * @param  TokenUsageRecorder  $usage  Registrador de actividad al que se delega solo tras autenticar.
     */
    public function __construct(TokenValidator $tokens, TokenUsageRecorder $usage)
    {
        $this->tokens = $tokens;
        $this->usage = $usage;
    }

    /**
     * Asocia el usuario del token válido a la petición actual.
     *
     * @param  Request  $request  Petición que transporta la cookie opaca y recibirá el usuario resuelto.
     * @param  Closure(Request): Response  $next  Siguiente manejador de la cadena, invocado solo si la petición supera el middleware.
     *
     * @throws AuthenticationException Cuando el token falta, es inválido o ha caducado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('auth_tokens.cookie.name');
        $plainTextToken = is_string($cookieName) ? $request->cookie($cookieName) : null;
        $accessToken = $this->tokens->validate(is_string($plainTextToken) ? $plainTextToken : null);

        if (! $accessToken instanceof AccessToken) {
            throw new AuthenticationException('Es necesario iniciar sesión para acceder a este recurso.');
        }

        $request->setUserResolver(
            /**
             * Expone a Laravel exclusivamente el usuario de la sesión validada.
             */
            static fn () => $accessToken->user);
        $request->attributes->set(AccessToken::class, $accessToken);
        $this->usage->record($accessToken);

        return $next($request);
    }
}
