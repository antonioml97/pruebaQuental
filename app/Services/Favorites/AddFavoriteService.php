<?php

declare(strict_types=1);

/**
 * Añade un favorito de forma idempotente para el usuario indicado.
 */

namespace App\Services\Favorites;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Añade un favorito de forma idempotente para el usuario indicado.
 */
final class AddFavoriteService
{
    /**
     * Garantiza de forma idempotente que el personaje sea favorito del usuario.
     *
     * @param  User  $user  Usuario local al que pertenece la operación; no se consultan otras cuentas.
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     */
    public function add(User $user, int $externalId): Character
    {
        $character = Character::query()->where('external_id', $externalId)->firstOrFail();
        $timestamp = now();

        DB::table('favorite_characters')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $character;
    }
}
