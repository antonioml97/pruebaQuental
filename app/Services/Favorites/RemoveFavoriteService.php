<?php

declare(strict_types=1);

/**
 * Elimina un favorito sin afectar las relaciones de otras cuentas.
 */

namespace App\Services\Favorites;

use App\Models\Character;
use App\Models\User;

/**
 * Elimina un favorito sin afectar las relaciones de otras cuentas.
 */
final class RemoveFavoriteService
{
    /**
     * Retira el favorito del usuario sin afectar relaciones de otras cuentas.
     *
     * @param  User  $user  Usuario local al que pertenece la operación; no se consultan otras cuentas.
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     */
    public function remove(User $user, int $externalId): void
    {
        $character = Character::query()->where('external_id', $externalId)->firstOrFail();

        $user->favoriteCharacters()->detach($character->getKey());
    }
}
