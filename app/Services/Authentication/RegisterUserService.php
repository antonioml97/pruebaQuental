<?php

declare(strict_types=1);

/**
 * Registra un usuario y su primera sesión en una única transacción.
 */

namespace App\Services\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use App\Domain\Authentication\DTO\RegistrationData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registra un usuario y su primera sesión en una única transacción.
 */
final class RegisterUserService
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

    /**
     * Registra un usuario y emite su primera sesión de forma atómica.
     *
     * @param  RegistrationData  $data  Datos de alta ya validados; la contraseña se cifra al persistir el usuario.
     */
    public function register(RegistrationData $data): AuthenticationResultData
    {
        return DB::transaction(
            /**
             * Crea el usuario y emite la primera sesión dentro de la misma transacción.
             */
            function () use ($data): AuthenticationResultData {
                $user = User::query()->create([
                    'name' => $data->name,
                    'email' => $data->email,
                    'password' => $data->password,
                ]);

                return $this->tokens->generate($user);
            });
    }
}
