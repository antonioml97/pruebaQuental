<?php

declare(strict_types=1);

/**
 * Adapta la gestión privada de personajes favoritos al transporte HTTP.
 */

namespace App\Http\Controllers\Api\Favorites;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Favorites\FavoriteIndexRequest;
use App\Http\Resources\CharacterSummaryResource;
use App\Http\Resources\PaginatedCharacterCollection;
use App\Models\User;
use App\Services\Favorites\AddFavoriteService;
use App\Services\Favorites\ListFavoritesService;
use App\Services\Favorites\RemoveFavoriteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;

/**
 * Expone listado, alta idempotente y eliminación sin contener consultas.
 */
final class FavoriteCharacterController extends Controller
{
    /**
     * Devuelve únicamente los favoritos del usuario autenticado.
     *
     * @param  FavoriteIndexRequest  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     * @param  ListFavoritesService  $favorites  Consulta limitada a los favoritos del usuario autenticado.
     */
    public function index(
        FavoriteIndexRequest $request,
        ListFavoritesService $favorites,
    ): PaginatedCharacterCollection {
        return (new PaginatedCharacterCollection(
            $favorites->paginate(
                user: $this->authenticatedUser($request),
                perPage: $request->perPage(),
                page: $request->page(),
            ),
        ))->preserveQuery();
    }

    /**
     * Garantiza que el personaje público forme parte de los favoritos actuales.
     *
     * @param  string  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     * @param  Request  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     * @param  AddFavoriteService  $favorites  Alta idempotente del favorito para el usuario autenticado.
     */
    public function store(
        string $externalId,
        Request $request,
        AddFavoriteService $favorites,
    ): CharacterSummaryResource {
        return new CharacterSummaryResource(
            $favorites->add($this->authenticatedUser($request), (int) $externalId),
        );
    }

    /**
     * Elimina de forma idempotente el favorito perteneciente al usuario actual.
     *
     * @param  string  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     * @param  Request  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     * @param  RemoveFavoriteService  $favorites  Eliminación idempotente del favorito para el usuario autenticado.
     */
    public function destroy(
        string $externalId,
        Request $request,
        RemoveFavoriteService $favorites,
    ): Response {
        $favorites->remove($this->authenticatedUser($request), (int) $externalId);

        return response()->noContent();
    }

    /**
     * Obtiene el usuario garantizado por el middleware de autenticación.
     *
     * @param  Request  $request  Petición cuya identidad y sesión ya resolvió el middleware auth.token.
     */
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new LogicException('El middleware no resolvió un usuario autenticado.');
        }

        return $user;
    }
}
