<?php

declare(strict_types=1);

/**
 * Implementa registro, acceso y revocación mediante tokens propios.
 */

namespace App\Services\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Domain\Authentication\DTO\CredentialsData;
use App\Domain\Authentication\DTO\RegistrationData;
use App\Domain\Authentication\Exceptions\InvalidCredentialsException;
use App\Models\AccessToken;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Coordina usuarios y tokens sin conocer cookies, cabeceras ni respuestas HTTP.
 */
final class TokenAuthenticationService
{
    /** Longitud en bytes aleatorios del secreto antes de codificarlo en hexadecimal. */
    private const TOKEN_BYTES = 32;

    /** Hash de coste productivo usado para no revelar si un correo existe por tiempo de respuesta. */
    private const DUMMY_PASSWORD_HASH = '$2y$12$Qb8JkZGuChqjabZhfFDXwObg9D62z.R04hBeZCS4BHgGdh94d0hpW';

    /**
     * Registra un usuario y emite su primera sesión de forma atómica.
     */
    public function register(RegistrationData $data): AuthenticationResultData
    {
        return DB::transaction(function () use ($data): AuthenticationResultData {
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => $data->password,
            ]);

            return $this->issueToken($user);
        });
    }

    /**
     * Comprueba credenciales y emite una sesión independiente.
     *
     * @throws InvalidCredentialsException Cuando las credenciales no coinciden.
     */
    public function login(CredentialsData $data): AuthenticationResultData
    {
        $user = User::query()->where('email', $data->email)->first();
        $passwordHash = $user instanceof User ? $user->password : self::DUMMY_PASSWORD_HASH;
        $passwordMatches = Hash::check($data->password, $passwordHash);

        if (! $user instanceof User || ! $passwordMatches) {
            throw new InvalidCredentialsException;
        }

        return $this->issueToken($user);
    }

    /**
     * Revoca exclusivamente la sesión utilizada en la petición actual.
     */
    public function logout(AccessToken $accessToken): void
    {
        $accessToken->delete();
    }

    /**
     * Genera un secreto, persiste solo su hash y devuelve el valor efímero.
     */
    private function issueToken(User $user): AuthenticationResultData
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
