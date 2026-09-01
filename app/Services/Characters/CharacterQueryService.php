<?php

declare(strict_types=1);

/**
 * Consulta personajes sincronizados sin acoplar el transporte HTTP a Eloquent.
 */

namespace App\Services\Characters;

use App\Domain\Characters\DTO\CharacterFiltersData;
use App\Models\Character;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Aplica filtros, orden estable, paginación y carga del detalle de personajes.
 */
final class CharacterQueryService
{
    /**
     * Obtiene una página ordenada de personajes que cumplen los filtros.
     *
     * @return LengthAwarePaginator<int, Character>
     */
    public function paginate(CharacterFiltersData $filters): LengthAwarePaginator
    {
        $query = Character::query();

        if ($filters->name !== null) {
            $query->where('name', 'like', '%'.$filters->name.'%');
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->species !== null) {
            $query->where('species', $filters->species);
        }

        if ($filters->gender !== null) {
            $query->where('gender', $filters->gender);
        }

        return $query
            ->orderBy('external_id')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    /**
     * Obtiene un personaje por su identificador público con todo su detalle.
     */
    public function findByExternalId(int $externalId): Character
    {
        return Character::query()
            ->with([
                'origin',
                'currentLocation',
                'episodes' => static fn (BelongsToMany $relation): BelongsToMany => $relation
                    ->orderBy('episodes.external_id'),
            ])
            ->where('external_id', $externalId)
            ->firstOrFail();
    }
}
