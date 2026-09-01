<?php

declare(strict_types=1);

/**
 * Autentica peticiones mediante el token opaco almacenado en una cookie.
 */

namespace App\Http\Middleware;

use App\Models\AccessToken;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve, compara y comprueba la caducidad de la sesión presentada.
 */
final class AuthenticateAccessToken
{
    /** Minutos mínimos entre actualizaciones de la última utilización. */
    private const LAST_USED_UPDATE_INTERVAL = 5;

    /**
     * Asocia el usuario del token válido a la petición actual.
     *
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException Cuando el token falta, es inválido o ha caducado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessToken = $this->resolveAccessToken($request);

        if (! $accessToken instanceof AccessToken) {
            throw new AuthenticationException;
        }

        $request->setUserResolver(static fn () => $accessToken->user);
        $request->attributes->set(AccessToken::class, $accessToken);
        $this->recordUsageWhenNecessary($accessToken);

        return $next($request);
    }

    /**
     * Resuelve el identificador y compara en tiempo constante el secreto presentado.
     */
    private function resolveAccessToken(Request $request): ?AccessToken
    {
        $cookieName = config('auth_tokens.cookie.name');
        $plainTextToken = is_string($cookieName) ? $request->cookie($cookieName) : null;

        if (! is_string($plainTextToken)) {
            return null;
        }

        [$identifier, $secret] = array_pad(explode('|', $plainTextToken, 2), 2, null);

        if (! is_string($identifier)
            || ! ctype_digit($identifier)
            || ! is_string($secret)
            || ! preg_match('/\A[a-f0-9]{64}\z/', $secret)
        ) {
            return null;
        }

        $accessToken = AccessToken::query()->with('user')->find((int) $identifier);

        if (! $accessToken instanceof AccessToken
            || ! hash_equals($accessToken->token_hash, hash('sha256', $secret))
            || $accessToken->expires_at->isPast()
        ) {
            return null;
        }

        return $accessToken;
    }

    /**
     * Registra actividad sin escribir en la base de datos en cada petición.
     */
    private function recordUsageWhenNecessary(AccessToken $accessToken): void
    {
        if ($accessToken->last_used_at !== null
            && $accessToken->last_used_at->isAfter(now()->subMinutes(self::LAST_USED_UPDATE_INTERVAL))
        ) {
            return;
        }

        $accessToken->forceFill(['last_used_at' => now()])->saveQuietly();
    }
}
