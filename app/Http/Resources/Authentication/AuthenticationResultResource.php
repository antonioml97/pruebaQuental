<?php

declare(strict_types=1);

/**
 * Define la respuesta pública tras registrar o autenticar un usuario.
 */

namespace App\Http\Resources\Authentication;

use App\Domain\Authentication\DTO\AuthenticationResultData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expone usuario y caducidad sin incluir nunca el token reservado a la cookie.
 *
 * @mixin AuthenticationResultData
 */
final class AuthenticationResultResource extends JsonResource
{
    /**
     * Transforma una autenticación satisfactoria al contrato público.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
            ],
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
        ];
    }
}
