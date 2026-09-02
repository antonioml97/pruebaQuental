<?php

declare(strict_types=1);

/**
 * Emite tokens opacos sin conocer su transporte HTTP.
 */

namespace App\Services\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Models\User;
use DateTimeImmutable;
use LogicException;

/**
 * Genera secretos criptográficos y persiste únicamente su hash y caducidad.
 */
final class TokenGenerator
{
    /** Longitud del secreto aleatorio antes de codificarlo en hexadecimal. */
    private const TOKEN_BYTES = 32;

    /**
     * Emite una sesión independiente para el usuario indicado.
     *
     * @param  User  $user  Usuario local al que pertenece la operación; no se consultan otras cuentas.
     *
     * @throws LogicException Cuando la duración configurada no es positiva.
     */
    public function generate(User $user): AuthenticationResultData
    {
        $lifetimeMinutes = (int) config('auth_tokens.lifetime_minutes');

        if ($lifetimeMinutes < 1) {
            throw new LogicException('La duración configurada del token debe ser positiva.');
        }

        $secret = bin2hex(random_bytes(self::TOKEN_BYTES));
        $expiresAt = now()->addMinutes($lifetimeMinutes)->toImmutable();
        $accessToken = $user->accessTokens()->create([
            'name' => 'vue-web',
            'token_hash' => hash('sha256', $secret),
            'expires_at' => $expiresAt,
        ]);

        return new AuthenticationResultData(
            userId: (int) $user->getKey(),
            name: $user->name,
            email: $user->email,
            plainTextToken: $accessToken->getKey().'|'.$secret,
            expiresAt: DateTimeImmutable::createFromInterface($expiresAt),
        );
    }
}
