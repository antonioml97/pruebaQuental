<?php

declare(strict_types=1);

/**
 * Valida sesiones opacas con independencia de cookies o peticiones HTTP.
 */

namespace App\Services\Authentication;

use App\Models\AccessToken;

/**
 * Comprueba formato, secreto y caducidad sin modificar la sesión persistida.
 */
final class TokenValidator
{
    /**
     * Resuelve una sesión válida sin prolongar su caducidad.
     *
     * @param  string|null  $plainTextToken  Credencial opaca de la cookie, o null si no se ha enviado.
     * @return AccessToken|null Sesión con su usuario cargado, o null si la credencial falta, es inválida o ha caducado.
     */
    public function validate(?string $plainTextToken): ?AccessToken
    {
        if ($plainTextToken === null) {
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
}
