<?php

declare(strict_types=1);

/**
 * Lista exclusivamente los favoritos del usuario indicado.
 */

namespace App\Services\Favorites;

use App\Models\Character;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lista exclusivamente los favoritos del usuario indicado.
 */
final class ListFavoritesService
{
    /**
     * Obtiene una página estable de favoritos pertenecientes al usuario.
     *
     * @param  User  $user  Usuario local al que pertenece la operación; no se consultan otras cuentas.
     * @param  int  $perPage  Tamaño de página previamente validado por la capa de entrada.
     * @param  int  $page  Página solicitada explícitamente, comenzando en uno.
     * @return LengthAwarePaginator<int, Character>
     */
    public function paginate(User $user, int $perPage, int $page = 1): LengthAwarePaginator
    {
        return $user->favoriteCharacters()
            ->orderByPivot('created_at', 'desc')
            ->orderBy('characters.external_id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
