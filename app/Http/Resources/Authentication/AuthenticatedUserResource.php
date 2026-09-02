<?php

declare(strict_types=1);

/**
 * Define la representación del usuario autenticado actual.
 */

namespace App\Http\Resources\Authentication;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expone únicamente la identidad pública necesaria para inicializar Vue.
 *
 * @mixin User
 */
final class AuthenticatedUserResource extends JsonResource
{
    /**
     * Transforma al usuario al contrato público de autenticación.
     *
     * @param  Request  $request  Contexto HTTP recibido por Laravel durante la serialización del recurso.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
