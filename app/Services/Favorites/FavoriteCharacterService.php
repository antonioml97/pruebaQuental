<?php

declare(strict_types=1);

/**
 * Gestiona los personajes favoritos de un usuario autenticado.
 */

namespace App\Services\Favorites;

use App\Models\Character;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Encapsula consultas y mutaciones de favoritos sin conocer el transporte HTTP.
 */
final class FavoriteCharacterService
{
    /**
     * Obtiene una página estable de favoritos pertenecientes al usuario.
     *
     * @return LengthAwarePaginator<int, Character>
     */
    public function paginate(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->favoriteCharacters()
            ->orderByPivot('created_at', 'desc')
            ->orderBy('characters.external_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Garantiza de forma idempotente que el personaje sea favorito del usuario.
     */
    public function add(User $user, int $externalId): Character
    {
        $character = $this->findCharacter($externalId);
        $timestamp = now();

        DB::table('favorite_characters')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'character_id' => $character->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $character;
    }

    /**
     * Retira el favorito del usuario sin afectar relaciones de otras cuentas.
     */
    public function remove(User $user, int $externalId): void
    {
        $character = $this->findCharacter($externalId);

        $user->favoriteCharacters()->detach($character->getKey());
    }

    /**
     * Resuelve un personaje mediante el identificador público de la API.
     */
    private function findCharacter(int $externalId): Character
    {
        return Character::query()->where('external_id', $externalId)->firstOrFail();
    }
}
