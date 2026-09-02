<?php

declare(strict_types=1);

/**
 * Consulta personajes sincronizados sin acoplar el transporte HTTP a Eloquent.
 */

namespace App\Services\Characters;

use App\Domain\Characters\DTO\CharacterFiltersData;
use App\Models\Character;
use App\Models\Episode;
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
     * @param  CharacterFiltersData  $filters  Criterios ya validados, incluidos los límites de paginación.
     * @param  int  $page  Página solicitada explícitamente, comenzando en uno.
     * @return LengthAwarePaginator<int, Character>
     */
    public function paginate(CharacterFiltersData $filters, int $page = 1): LengthAwarePaginator
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
            ->paginate(perPage: $filters->perPage, page: $page);
    }

    /**
     * Obtiene un personaje por su identificador público con todo su detalle.
     *
     * @param  int  $externalId  Identificador público del proveedor, no la clave local de Eloquent.
     */
    public function findByExternalId(int $externalId): Character
    {
        return Character::query()
            ->with([
                'origin',
                'currentLocation',
                'episodes' =>
                    /**
                     * Mantiene un orden estable de episodios dentro del detalle.
                     *
                     * @param  BelongsToMany<Episode, Character>  $relation  Relación del personaje sobre la que se aplica el orden.
                     * @return BelongsToMany<Episode, Character> Relación ordenada por el identificador externo del episodio.
                     */
                    static fn (BelongsToMany $relation): BelongsToMany => $relation
                        ->orderBy('episodes.external_id'),
            ])
            ->where('external_id', $externalId)
            ->firstOrFail();
    }
}
