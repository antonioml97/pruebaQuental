<?php

declare(strict_types=1);

/**
 * Adapta la gestión privada de personajes favoritos al transporte HTTP.
 */

namespace App\Http\Controllers\Api\Favorites;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Favorites\FavoriteIndexRequest;
use App\Http\Resources\CharacterSummaryResource;
use App\Models\User;
use App\Services\Favorites\FavoriteCharacterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use LogicException;

/**
 * Expone listado, alta idempotente y eliminación sin contener consultas.
 */
final class FavoriteCharacterController extends Controller
{
    /**
     * Devuelve únicamente los favoritos del usuario autenticado.
     */
    public function index(
        FavoriteIndexRequest $request,
        FavoriteCharacterService $favorites,
    ): AnonymousResourceCollection {
        return CharacterSummaryResource::collection(
            $favorites->paginate($this->authenticatedUser($request), $request->perPage()),
        );
    }

    /**
     * Garantiza que el personaje público forme parte de los favoritos actuales.
     */
    public function store(
        string $externalId,
        Request $request,
        FavoriteCharacterService $favorites,
    ): CharacterSummaryResource {
        return new CharacterSummaryResource(
            $favorites->add($this->authenticatedUser($request), (int) $externalId),
        );
    }

    /**
     * Elimina de forma idempotente el favorito perteneciente al usuario actual.
     */
    public function destroy(
        string $externalId,
        Request $request,
        FavoriteCharacterService $favorites,
    ): Response {
        $favorites->remove($this->authenticatedUser($request), (int) $externalId);

        return response()->noContent();
    }

    /**
     * Obtiene el usuario garantizado por el middleware de autenticación.
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
