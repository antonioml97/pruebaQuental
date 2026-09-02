<?php

declare(strict_types=1);

/**
 * Verifica credenciales y emite una sesión independiente.
 */

namespace App\Services\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Domain\Authentication\DTO\CredentialsData;
use App\Domain\Authentication\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Verifica credenciales y emite una sesión independiente.
 */
final class LoginService
{
    /** Emisor de sesiones opacas. */
    private readonly TokenGenerator $tokens;

    /**
     * Recibe el colaborador específico del caso de uso.
     *
     * @param  TokenGenerator  $tokens  Emisor que persiste el hash y devuelve el secreto solo para su entrega.
     */
    public function __construct(TokenGenerator $tokens)
    {
        $this->tokens = $tokens;
    }

    /** Evita omitir el trabajo de hashing cuando el correo no existe. */
    private const DUMMY_PASSWORD_HASH = '$2y$12$Qb8JkZGuChqjabZhfFDXwObg9D62z.R04hBeZCS4BHgGdh94d0hpW';

    /**
     * Comprueba credenciales y emite una sesión independiente.
     *
     * @param  CredentialsData  $data  Correo normalizado y contraseña que se comprobarán sin revelar cuál falla.
     *
     * @throws InvalidCredentialsException Cuando las credenciales no coinciden.
     */
    public function login(CredentialsData $data): AuthenticationResultData
    {
        $user = User::query()->where('email', $data->email)->first();
        $passwordHash = $user instanceof User ? $user->password : self::DUMMY_PASSWORD_HASH;
        $passwordMatches = Hash::check($data->password, $passwordHash);

        if (! $user instanceof User || ! $passwordMatches) {
            throw new InvalidCredentialsException('Las credenciales proporcionadas no son válidas.');
        }

        return $this->tokens->generate($user);
    }
}
