<?php

declare(strict_types=1);

/**
 * Guarda un personaje y sus episodios usando referencias locales ya resueltas.
 */

namespace App\Services\Characters;

use App\Domain\Characters\DTO\CharacterData;
use App\Models\Character;

/**
 * Guarda un personaje y sus episodios usando referencias locales ya resueltas.
 */
final class CharacterPersister
{
    /**
     * Persiste atributos y relaciones dentro de la transacción abierta por el catálogo.
     *
     * @param  CharacterData  $data  Datos externos del personaje, separados de las claves locales de sus relaciones.
     * @param  int|null  $originLocationId  Clave local del origen ya resuelta, o null si se desconoce.
     * @param  int|null  $currentLocationId  Clave local de la ubicación actual ya resuelta, o null si se desconoce.
     * @param  list<int>  $episodeIds  Identificadores locales de episodios, no externos.
     */
    public function persist(
        CharacterData $data,
        ?int $originLocationId,
        ?int $currentLocationId,
        array $episodeIds,
    ): Character {
        $character = Character::query()->updateOrCreate(
            ['external_id' => $data->externalId],
            [
                'name' => $data->name,
                'status' => $data->status,
                'species' => $data->species,
                'type' => $data->type,
                'gender' => $data->gender,
                'image_url' => $data->imageUrl,
                'origin_location_id' => $originLocationId,
                'current_location_id' => $currentLocationId,
            ],
        );

        $character->episodes()->sync($episodeIds);

        return $character;
    }
}
